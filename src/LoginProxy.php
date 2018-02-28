<?php

namespace Montross50\PassportProxy;


use Illuminate\Foundation\Application;
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
        $identifier = $this->config->get('passport-proxy.user_identifier');
        $user = $this->userRepository->getByIdentifier($identifier,$identifierValue);

        if (!is_null($user)) {
            return $this->proxy('password', [
                $identifier => $identifierValue,
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
        $data = array_merge($data, [
            'client_id'     => env('PASSPORT_CLIENT'),
            'client_secret' => env('PASSPORT_SECRET'),
            'grant_type'    => $grantType
        ]);

        $response = $this->apiConsumer->post('/oauth/token', $data);

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

        return [
            'access_token'  => $result->access_token,
            'expires_in'    => $result->expires_in,
            'refresh_token' => $result->refresh_token
        ];
    }

    /**
     * Logs out the user. We revoke access token and refresh token.
     * Also instruct the client to forget the refresh cookie.
     */
    public function logout()
    {


        if($this->config->get('passport-proxy.passport_location') === 'local'){
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
        }else{
           //todo
        }



        $this->cookie->queue($this->cookie->forget(self::REFRESH_TOKEN));
    }
}
