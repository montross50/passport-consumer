<?php namespace Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Http\Kernel;


class PassportConsumerControllerTest extends TestCase
{

    protected $routePrefix;
    protected $routeName;

    public function setUp()
    {
        parent::setUp();
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
        //$login = Mocker
        $this->configRoute('pg');
        $email = 'foo@example.com';
        $password = 'bar';
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
        $email = 'foo@example.com';
        $password = 'bar';
        factory(User::class)->create(['email' => $email]);
        $response = $this->json('POST', 'login', ['email' => $email, 'password' => $password]);

        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ]);
        $accessToken = $response->json()['access_token'];
        $refreshToken = $response->json()['refresh_token'];
        $id = (new \Lcobucci\JWT\Parser())->parse($accessToken)->getHeader('jti');
        $token = \Laravel\Passport\Token::find($id);
        $token->expires_at = \Carbon\Carbon::now()->subMinute();
        $token->save();
        $result = $this->json('POST', 'login/refresh', ['refresh_token' => $refreshToken], [
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
        $email = 'foo@example.com';
        $password = 'bar';
        factory(User::class)->create(['email' => $email]);
        $response = $this->json('POST', 'login', ['email' => $email, 'password' => $password]);

        $response
            ->assertStatus(200)
            ->assertJson([
                "access_token"  => true,
                "expires_in"    => true,
                'refresh_token' => true
            ]);
        $accessToken = $response->json()['access_token'];
        $result = $this->json('POST', 'logout', [], [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $result
            ->assertStatus(204);
        $id = (new \Lcobucci\JWT\Parser())->parse($accessToken)->getHeader('jti');
        $token = \Laravel\Passport\Token::find($id);
        $this->assertEquals(1, $token->revoked);
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
