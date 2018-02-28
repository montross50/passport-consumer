<?php

namespace Montross50\PassportConsumer\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Montross50\PassportConsumer\LoginProxy;
use Illuminate\Config\Repository as Config;
use Montross50\PassportConsumer\Handlers\PostAuthorizeCallbackInterface;

class PassportConsumerController extends Controller
{
    use ValidatesRequests;

    private $loginProxy;
    private $userIdentifier;
    private $config;
    private $authorizeCallback;

    public function __construct(LoginProxy $loginProxy, Config $config, PostAuthorizeCallbackInterface $authorizeCallback)
    {
        $this->loginProxy = $loginProxy;
        $this->userIdentifier = $config->get('passport-consumer.user_identifier');
        $this->config = $config;
        $this->authorizeCallback = $authorizeCallback;
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            $this->userIdentifier => 'required',
            'password' => 'required'
        ]);

        $email = $request->get($this->userIdentifier);
        $password = $request->get('password');
        $scope = $request->get('scope', "");
        return Response::json($this->loginProxy->attemptLogin($email, $password, $scope));
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
        $routeName = config('passport-consumer.route_name_access');
        $routePrefix = config('passport-consumer.route_prefix');
        $appUrl = config('passport-consumer.app_url');
        $query = http_build_query([
            'client_id' => $this->config->get('passport-consumer.passport_id_access'),
            'redirect_uri' => "$appUrl/$routePrefix/$routeName/callback",
            'response_type' => 'code',
            'scope' => '',
        ]);
        $loc = $this->config->get('passport-consumer.passport_location');
        $passportLocation =  $loc === 'local' ? '' : $loc;
        return redirect("$passportLocation/oauth/authorize?$query");
    }

    public function callback(Request $request)
    {
        $routeName = config('passport-consumer.route_name_access');
        $routePrefix = config('passport-consumer.route_prefix');
        $appUrl = config('passport-consumer.app_url');
        $result = $this->loginProxy->proxy('authorization_code', [
            'redirect_uri' => "$appUrl/$routePrefix/$routeName/callback",
            'code' => $request->get('code')
        ]);

        return $this->authorizeCallback->handle($result);
    }
}
