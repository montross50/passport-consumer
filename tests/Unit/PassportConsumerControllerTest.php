<?php namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Montross50\PassportConsumer\LoginProxy;
use Montross50\PassportConsumer\Handlers\PostAuthorizeCallbackInterface;
use Montross50\PassportConsumer\Handlers\DefaultPostAuthorizeCallback;
use Tests\TestCase;
use Tests\User;

class PassportConsumerControllerTest extends TestCase
{

    protected $routePrefix;
    protected $routeName;

    public function setUp()
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'mysql']);
        $auth_provider = config('passport-consumer.auth_provider_key');
        config([$auth_provider=>User::class]);
        $this->seedPassport();
    }

    public function configRoute($type)
    {
        $this->routeName = '/'.config('passport-consumer.route_name_'.$type);
        $this->routePrefix = config('passport-consumer.route_prefix');
    }

    public function testLoginReturnsToken()
    {
        $email = 'foo@example.com';
        $password = 'bar';
        $login = \Mockery::mock(LoginProxy::class);
        $login->shouldReceive('attemptLogin')
            ->with($email, $password, "")
            ->andReturn([
               'access_token'=>'foobar',
               'expires_in'=>'598',
               'refresh_token'=>'foobar'
            ]);
        $this->app->instance(LoginProxy::class, $login);
        $this->configRoute('pg');

        factory(User::class)->create(['email' => $email,'password'=>$password]);
        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => $password]);
        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ]);
    }

    public function testRefreshWorks()
    {
        $this->configRoute('pg');
        $refreshToken = 'foobar';
        $accessToken = '';
        $login = \Mockery::mock(LoginProxy::class);
        $login->shouldReceive('attemptRefresh')
            ->with($refreshToken)
            ->andReturn([
                'access_token'=>'foobar2',
                'expires_in'=>'598',
                'refresh_token'=>'foobar2'
            ]);
        $this->app->instance(LoginProxy::class, $login);
        $this->withoutMiddleware();
        $result = $this->json('POST', $this->routePrefix . $this->routeName .'/refresh', ['refresh_token' => $refreshToken]);
        $data = $result->json();
        $result
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ]);
        $this->assertNotEquals($accessToken, $data['access_token']);
        $this->assertNotEquals($refreshToken, $data['refresh_token']);
    }

    public function testLogout()
    {
        $this->configRoute('pg');
        $login = \Mockery::mock(LoginProxy::class);
        $login->shouldReceive('logout');
        $this->app->instance(LoginProxy::class, $login);
        $this->withoutMiddleware();
        $result = $this->json('POST', $this->routePrefix . $this->routeName .'/logout', []);
        $result
            ->assertStatus(204);
    }

    public function testRedirectLocal()
    {
        $this->configRoute('access');
        $loc = '';
        $appUrl = config('passport-consumer.app_url');
        $query = http_build_query([
            'client_id' => config('passport-consumer.passport_id_access'),
            'redirect_uri' => "$appUrl/$this->routePrefix$this->routeName/callback",
            'response_type' => 'code',
            'scope' => '',
        ]);

        $result = $this->json('GET', $this->routePrefix . $this->routeName .'/redirect', []);
        $result
            ->assertRedirect("$loc/oauth/authorize?$query");
    }

    public function testRedirectRemote()
    {
        config(['passport-consumer.app_url'=>'foobar.com']);
        $this->configRoute('access');
        $loc = '';
        $appUrl = config('passport-consumer.app_url');
        $query = http_build_query([
            'client_id' => config('passport-consumer.passport_id_access'),
            'redirect_uri' => "$appUrl/$this->routePrefix$this->routeName/callback",
            'response_type' => 'code',
            'scope' => '',
        ]);

        $result = $this->json('GET', $this->routePrefix . $this->routeName .'/redirect', []);
        $result
            ->assertRedirect("$loc/oauth/authorize?$query");
    }

    public function testCallbackDefault()
    {
        $this->configRoute('access');
        $code = 'foobar';
        $login = \Mockery::mock(LoginProxy::class);
        $appUrl = config('passport-consumer.app_url');
        $login->shouldReceive('proxy')
            ->with('authorization_code', ['redirect_uri'=>"$appUrl/$this->routePrefix$this->routeName/callback",'code'=>$code])
            ->andReturn([
                'access_token'=>'foobar',
                'expires_in'=>'598',
                'refresh_token'=>'foobar'
            ]);
        $this->app->instance(LoginProxy::class, $login);
        $result = $this->json('GET', $this->routePrefix . $this->routeName .'/callback?code='.$code, []);
        $result
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ]);
    }

    public function testCallbackCustom()
    {

        $this->app->bind(PostAuthorizeCallbackInterface::class, function () {
            $func = function ($token) {
                return "wow it worked ". current($token)['access_token'];
            };
            return new DefaultPostAuthorizeCallback($func);
        });
        $this->configRoute('access');
        $code = 'foobar';
        $login = \Mockery::mock(LoginProxy::class);
        $appUrl = config('passport-consumer.app_url');
        $login->shouldReceive('proxy')
            ->with('authorization_code', ['redirect_uri'=>"$appUrl/$this->routePrefix$this->routeName/callback",'code'=>$code])
            ->andReturn([
                'access_token'=>'foobar',
                'expires_in'=>'598',
                'refresh_token'=>'foobar'
            ]);
        $this->app->instance(LoginProxy::class, $login);
        $result = $this->json('GET', $this->routePrefix . $this->routeName .'/callback?code='.$code, []);
        $result
            ->assertStatus(200)
            ->assertSee("wow it worked foobar");
    }

    public function testRefreshWorksAccess()
    {
        $this->configRoute('access');
        $refreshToken = 'foobar';
        $accessToken = '';
        $login = \Mockery::mock(LoginProxy::class);
        $login->shouldReceive('attemptRefresh')
            ->with($refreshToken)
            ->andReturn([
                'access_token'=>'foobar2',
                'expires_in'=>'598',
                'refresh_token'=>'foobar2'
            ]);
        $this->app->instance(LoginProxy::class, $login);
        $this->withoutMiddleware();
        $result = $this->json('POST', $this->routePrefix . $this->routeName .'/refresh', ['refresh_token' => $refreshToken]);
        $data = $result->json();
        $result
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ]);
        $this->assertNotEquals($accessToken, $data['access_token']);
        $this->assertNotEquals($refreshToken, $data['refresh_token']);
    }

    public function seedPassport()
    {
        DB::table('oauth_clients')->truncate();

        DB::table('oauth_clients')->insert([
            [
                "id" => 1,
                "user_id" => null,
                "name" => "Access Client",
                "secret" => "xafLtLnVqpq3WJLVeUC4SIoWFekDkgmrt11q0Tqv",
                "redirect" => "http://localhost",
                "personal_access_client" => 0,
                "password_client" => 0,
                "revoked" => 0,
                "created_at" => "2018-02-18 20:48:46",
                "updated_at" => "2018-02-18 20:48:46"
            ],
            [
                "id" => 2,
                "user_id" => null,
                "name" => "Password Grant Client",
                "secret" => "WvY6bQpPGCZ0K90XPGigiEHdRbXg2mJuzNxvLCaU",
                "redirect" => "http://localhost",
                "personal_access_client" => 0,
                "password_client" => 1,
                "revoked" => 0,
                "created_at" => "2018-02-18 20:48:46",
                "updated_at" => "2018-02-18 20:48:46"
            ]
        ]);
    }
}
