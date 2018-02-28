<?php

namespace Montross50\PassportConsumer\Http\Controllers;


use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Montross50\PassportConsumer\LoginProxy;
use Illuminate\Config\Repository as Config;

class ProxyLoginController extends Controller
{
    use ValidatesRequests;

    private $loginProxy;
    private $userIdentifier;
    private $config;

    public function __construct(LoginProxy $loginProxy, Config $config)
    {
        $this->loginProxy = $loginProxy;
        $this->userIdentifier = $config->get('passport-consumer.user_identifier');
        $this->config = $config;
    }

    public function login(Request $request)
    {
        $this->validate($request,[
            $this->userIdentifier => 'required',
            'password' => 'required'
        ]);

        $email = $request->get($this->userIdentifier);
        $password = $request->get('password');
        $scope = $request->get('scope', "");
        return Response::json($this->loginProxy->attemptLogin($email, $password, $scope));
    }

    public function getScopes()
    {
        $scopes = Passport::scopes()->groupBy('id')->keys()->toArray();
        return Response::json(["data" => $scopes]);
    }

    public function currentScope(Request $request)
    {
        $token = $request->user()->token();
        return Response::json(["data" => $token->scopes]);
    }

    public function refresh(Request $request)
    {
        $refresh_token = $request->get('refresh_token', false);
        return Response::json($this->loginProxy->attemptRefresh($refresh_token));
    }

    public function logout()
    {
        $this->loginProxy->logout();
        return Response::json(null, 204);
    }

    public function redirect()
    {
        $query = http_build_query([
            'client_id' => 'client-id',
            'redirect_uri' => 'http://example.com/callback',
            'response_type' => 'code',
            'scope' => '',
        ]);

        return redirect($this->config->get('passport-consumer.authorize_url').$query);
    }
}
