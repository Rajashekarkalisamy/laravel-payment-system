<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthControllerUnitTest extends TestCase
{
    public function test_user_can_register()
    {
        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'secret123'
        ]);

        $userMock = \Mockery::mock('alias:' . User::class);
        $userMock->shouldReceive('create')->once()->withArgs(function ($data) {
            return $data['email'] === 'test@example.com' &&
                   Hash::check('secret123', $data['password']) === false; // hashed password
        })->andReturn(new User(['id' => 1, 'email' => 'test@example.com']));

        $auth = new AuthController();
        $response = $auth->register($request);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('token', $response->getData(true));
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = new User(['id' => 1, 'email' => 'test@example.com']);
        Auth::shouldReceive('attempt')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'secret'
        ]);

        $controller = new AuthController();
        $response = $controller->login($request);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('token', $response->getData(true));
    }
}

