<?php namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Montross50\PassportConsumer\LoginProxy;
use Montross50\PassportConsumer\Handlers\PostAuthorizeCallbackInterface;
use Montross50\PassportConsumer\Handlers\DefaultPostAuthorizeCallback;
use Tests\RemoteUser;
use Tests\TestCase;
use Tests\User;

class PassportConsumerControllerRemoteTest extends TestCase
{

    protected $routePrefix;
    protected $routeName;

    public function setUp()
    {
        parent::setUp();
        $auth_provider = config('passport-consumer.auth_provider_key');
        config(['passport-consumer.passport_location'=>'','passport-consumer.log_user_in'=>true,$auth_provider=>User::class,'auth.guards.api.driver' => 'token']);
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->seedPassport();
        $this->artisan('migrate', ['--database' => 'mysql']);
    }

    public function configRoute($type)
    {
        $this->routeName = '/'.config('passport-consumer.route_name_'.$type);
        $this->routePrefix = config('passport-consumer.route_prefix');
    }



    public function testLoginReturnsErrorOnInvalidCredentials()
    {
        $this->configRoute('pg');
        $email = 'foo@example.com';
        $password = 'bar';
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);

        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => 'wrong']);
        $response
            ->assertStatus(401);
    }

    public function testLoginReturnTokenWithExistingUser()
    {
        $this->configRoute('pg');
        $email = 'foo@example.com';
        $password = 'bar';
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);
        $field = config('passport-consumer.remote_user_identifier');
        $user->$field = $user->id;
        $user->save();
        $this->bootstrapUserRoute($user);
        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => $password]);
        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ])->assertSessionHas('remote_access_token')
            ->assertSessionHas('remote_refresh_token');
        $user->refresh();
        $result = $response->json();
        $this->assertEquals(Session::get('remote_refresh_token'), $user->remoteRefreshToken());
        $this->assertEquals($result['refresh_token'], $user->remoteRefreshToken());
        $this->assertEquals($user->api_token, $result['access_token']);
    }

    public function testLoginReturnTokenWithNewUser()
    {
        //This test isn't perfect as i am not using a truly remote version of passport. It gets close though.
        $this->configRoute('pg');
        $email = 'foo@example.com';
        $email2 = 'foo2@example.com';
        $password = 'bar';
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);
        $user2 = factory(RemoteUser::class)->create(['email' => $email2,'password'=>bcrypt($password)]);
        $this->bootstrapUserRoute($user2);
        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => $password]);
        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ])->assertSessionHas('remote_access_token')
            ->assertSessionHas('remote_refresh_token');
        $user = User::where('email', $email2)->get()->first();
        $result = $response->json();
        $this->assertEquals(Session::get('remote_refresh_token'), $user->remoteRefreshToken());
        $this->assertEquals($result['refresh_token'], $user->remoteRefreshToken());
        $this->assertEquals($user->api_token, $result['access_token']);
        $this->assertEquals($email2, $user->email);
        $field = config('passport-consumer.remote_user_identifier');
        $this->assertEquals($user2->id, $user->$field);
    }

    public function testLoginReturnTokenWithCustomTokenEndpoint()
    {
        $this->configRoute('pg');
        $email = 'foo@example.com';
        $password = 'bar';
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);
        $field = config('passport-consumer.remote_user_identifier');
        $user->$field = $user->id;
        $user->save();
        $end = 'unittest/token';
        config(['passport-consumer.token_endpoint'=> $end]);
        $this->bootstrapTokenRoute($user, $end);
        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => $password]);
        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ])->assertSessionHas('remote_access_token')
            ->assertSessionHas('remote_refresh_token');
        $user->refresh();
        $result = $response->json();
        $this->assertEquals(Session::get('remote_refresh_token'), $user->remoteRefreshToken());
        $this->assertEquals($result['refresh_token'], $user->remoteRefreshToken());
        $this->assertEquals($user->api_token, $result['access_token']);
    }

    public function testRefreshWorks()
    {
        $this->configRoute('pg');
        $email = 'foo@example.com';
        $password = 'bar';
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);
        $field = config('passport-consumer.remote_user_identifier');
        $user->$field = $user->id;
        $user->save();
        $this->bootstrapUserRoute($user);
        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => $password]);
        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ])->assertSessionHas('remote_access_token');
        $accessToken = $response->json()['access_token'];
        $refreshToken = $response->json()['refresh_token'];
        $result = $this->json('POST', $this->routePrefix . $this->routeName .'/refresh', ['refresh_token' => $refreshToken], [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
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
        $email = 'foo@example.com';
        $password = 'bar';
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);
        $field = config('passport-consumer.remote_user_identifier');
        $user->$field = $user->id;
        $user->save();
        $this->bootstrapUserRoute($user);
        $response = $this->json('POST', $this->routePrefix . $this->routeName . '/login', ['email' => $email, 'password' => $password]);
        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ])->assertSessionHas('remote_access_token');
        $accessToken = $response->json()['access_token'];
        $result = $this->json('POST', $this->routePrefix . $this->routeName .'/logout', [], [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $result
            ->assertStatus(204);
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
