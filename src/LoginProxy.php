<?php

namespace Montross50\PassportConsumer;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\Token;

class LoginProxy
{
    const REFRESH_TOKEN = 'refreshToken';

    private $apiConsumer;

    private $auth;

    private $cookie;

    private $db;

    private $request;

    private $userRepository;

    private $config;

    public function __construct(Application $app, UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->config = $app['config'];
        $this->apiConsumer = $app->make('apiconsumer');
        $this->auth = $app->make('auth');
        $this->cookie = $app->make('cookie');
        $this->db = $app->make('db');
        $this->request = $app->make('request');
    }

    /**
     * Attempt to create an access token using user credentials
     *
     * @param       $identifierValue
     * @param       $password
     * @param       $scope
     *
     * @return array
     */
    public function attemptLogin($identifierValue, $password, $scope = "")
    {
        $identifier = $this->config->get('passport-consumer.user_identifier');
        $user = $this->userRepository->getByIdentifier($identifier, $identifierValue);

        if (!is_null($user)) {
            return $this->proxy('password', [
                'username' => $identifierValue,
                'password' => $password,
                'scope'    => $scope
            ]);
        }

        throw new InvalidCredentialsException();
    }

    /**
     * Attempt to refresh the access token used a refresh token that
     * has been saved in a cookie
     * @param bool $refreshToken
     *
     * @return array
     */
    public function attemptRefresh($refreshToken = false)
    {
        if ($refreshToken === false) {
            $refreshToken = $this->request->cookie(self::REFRESH_TOKEN);
        }
        return $this->proxy('refresh_token', [
            'refresh_token' => $refreshToken
        ]);
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param string $grantType what type of grant type should be proxied
     * @param array  $data      the data to send to the server
     *
     * @return array access info
     */
    public function proxy($grantType, array $data = [])
    {
        switch ($grantType) {
            case 'authorization_code':
                $type = 'access';
                break;
            case 'password':
                $type = 'pg';
                break;
            case 'refresh_token':
                $name = Route::currentRouteName();
                switch ($name) {
                    case 'access_refresh':
                        $type = 'access';
                        break;
                    case 'pg_refresh':
                        $type = 'pg';
                        break;
                    default:
                        $type = 'pg';
                }
                break;
            default:
                $type = 'pg';
        }
        $data = array_merge($data, [
            'client_id'     => $this->config->get('passport-consumer.passport_id_'.$type),
            'client_secret' =>  $this->config->get('passport-consumer.passport_secret_'.$type),
            'grant_type'    => $grantType
        ]);

        $response = $this->apiConsumer->post($this->config->get('passport-consumer.token_endpoint'), $data);

        if (!$response->isSuccessful()) {
            throw new InvalidCredentialsException();
        }

        $result = json_decode($response->getContent());
        // Create a refresh token cookie
        $this->cookie->queue(
            self::REFRESH_TOKEN,
            $result->refresh_token,
            864000, // 10 days
            null,
            null,
            false,
            true // HttpOnly
        );
        $return = [
            'access_token'  => $result->access_token,
            'expires_in'    => $result->expires_in,
            'refresh_token' => $result->refresh_token,

        ];
        if ($this->config->get('passport-consumer.token_endpoint') !== '/oauth/token') {
            //in case you have a custom token endpoint that extends the token function. Perhaps to return user on success
            $return['result'] = json_decode(json_encode($result), true);
        }
        if ($this->config->get('passport-consumer.log_user_in')) {
            $this->loginUser($return);
        }
        return $return;
    }

    /**
     * Logs out the user. We revoke access token and refresh token.
     * Also instruct the client to forget the refresh cookie.
     */
    public function logout()
    {


        if ($this->config->get('passport-consumer.passport_location') === 'local') {
            /**
             * @var $accessToken Token
             */
            $accessToken = $this->auth->user()->token();
            $refreshToken = $this->db
                ->table('oauth_refresh_tokens')
                ->where('access_token_id', $accessToken->id)
                ->update([
                    'revoked' => true
                ]);
            $accessToken->revoke();
        } else {
            //This will not be a true "token" model. Just a function on the user to return
            $accessToken = $this->auth->user()->remoteAccessToken();
            $this->apiConsumer->post($this->config->get('passport-consumer.token_revoke_endpoint'), [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ]);
        }



        $this->cookie->queue($this->cookie->forget(self::REFRESH_TOKEN));
    }

    public function loginUser($tokenInfo)
    {

        if (isset($tokenInfo['result']) && isset($tokenInfo['result']['user'])) {
            $remoteUser = $tokenInfo['result']['user'];
        } else {
            $response = $this->apiConsumer->get($this->config->get('passport-consumer.user_endpoint'), [], [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $tokenInfo['access_token'],
            ]);
            $remoteUser = json_decode($response->getContent(), true);
        }
        $useSession = $this->config->get('passport-consumer.use_session');
        if ($useSession && !Session::isStarted()) {
            Session::start();
        }
        if ($this->config->get('passport-consumer.passport_location') !== 'local') {
            $remoteUserIdentifier = $this->config->get('passport-consumer.remote_user_identifier');
            $remoteUserField = $this->config->get('passport-consumer.remote_user_identifier_field');
            if (!isset($remoteUser[$remoteUserField])) {
                return null;
            }
            $user = $this->userRepository->getByIdentifier($remoteUserIdentifier, $remoteUser[$remoteUserField]);
            if (null === $user) {
                $remoteUser[$remoteUserIdentifier] = $remoteUser[$remoteUserField];
                $user = $this->userRepository->createUser($remoteUser);
            }
            $user->withRemoteAccessToken($tokenInfo['access_token']);
            $user->withRemoteRefreshToken($tokenInfo['refresh_token']);
            $user->api_token = $tokenInfo['access_token'];
            $user->save();
        } else {
            $model = $this->userRepository->getModel();
            $user = $this->userRepository->find($remoteUser[$model->getAuthIdentifierName()]);
        }

        $userAuth = Auth::user();
        if (null !== $userAuth && $userAuth->getAuthIdentifier() === $user->getAuthIdentifier()) {
            //we are already logged in
        } else {
            Auth::login($user);
            if ($useSession) {
                Session::put('access_token', $tokenInfo['access_token']);
                Session::put('refresh_token', $tokenInfo['refresh_token']);
                Session::put('expires_at', time() - $tokenInfo['expires_in']);
            }
        }
        return $user;
    }
}
