<?php namespace Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Montross50\PassportConsumer\InvalidCredentialsException;
use Montross50\PassportConsumer\LoginProxy;

class LoginProxyLocalTest extends TestCase
{
    /**
     * @var LoginProxy
     */
    protected $proxy;

    public function setUp()
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'mysql']);
        $auth_provider = config('passport-consumer.auth_provider_key');
        config(['auth.guards.api.driver' => 'passport']);
        config([$auth_provider=>User::class]);
        $this->seedPassport();
        $this->proxy = null;
        $this->proxy = app(LoginProxy::class);
    }

    /**
     * @expectedException \Montross50\PassportConsumer\InvalidCredentialsException
     */
    public function testLoginReturnsExceptionOnUserNotFound()
    {

        $email = 'foo@example.com';
        $password = 'bar';

        $result = $this->proxy->attemptLogin($email, $password);
    }

    public function testLoginReturnsToken()
    {

        $email = 'foo@example.com';
        $password = 'bar';
        factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);

        $result = $this->proxy->attemptLogin($email, $password);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
    }


    public function testRefreshWorks()
    {
        $email = 'foo@example.com';
        $password = 'bar';
        factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);

        $result = $this->proxy->attemptLogin($email, $password);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $id = (new \Lcobucci\JWT\Parser())->parse($result['access_token'])->getHeader('jti');
        $token = \Laravel\Passport\Token::find($id);
        $token->expires_at = \Carbon\Carbon::now()->subMinute();
        $token->save();
        $refresh = $this->proxy->attemptRefresh($result['refresh_token']);
        $this->assertArrayHasKey('access_token', $refresh);
        $this->assertArrayHasKey('refresh_token', $refresh);
        $this->assertArrayHasKey('expires_in', $refresh);
        $this->assertNotEquals($result['access_token'], $refresh['access_token']);
        $this->assertNotEquals($result['refresh_token'], $refresh['refresh_token']);
    }

    public function testLogout()
    {
        $email = 'foo@example.com';
        $password = 'bar';
        /**
         * @var $user User
         */
        $user = factory(User::class)->create(['email' => $email,'password'=>bcrypt($password)]);

        $result = $this->proxy->attemptLogin($email, $password);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $accessToken = $result['access_token'];
        $id = (new \Lcobucci\JWT\Parser())->parse($accessToken)->getHeader('jti');
        $token = \Laravel\Passport\Token::find($id);
        $user->withAccessToken($token);
        Auth::setUser($user);
        $this->proxy->logout();


        $token->refresh();
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
