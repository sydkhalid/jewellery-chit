<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receive_sanctum_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.user.role', 'Admin')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'permissions',
                    ],
                ],
            ]);
    }

    public function test_manager_can_login_and_receive_sanctum_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'manager@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.user.email', 'manager@example.com')
            ->assertJsonPath('data.user.role', 'Manager')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user',
                ],
            ]);
    }

    public function test_staff_can_login_and_receive_sanctum_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.user.email', 'staff@example.com')
            ->assertJsonPath('data.user.role', 'Staff')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user',
                ],
            ]);
    }

    public function test_user_without_panel_role_cannot_login_to_api(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'User does not have access to this application')
            ->assertJsonPath('data', []);
    }

    public function test_api_user_route_requires_sanctum_token(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_api_user_route_returns_resource_for_authenticated_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $token = $user->createToken('mobile-api');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Authenticated user fetched successfully')
            ->assertJsonPath('data.user.email', 'admin@example.com');
    }

    public function test_logout_revokes_current_sanctum_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $token = $user->createToken('mobile-api');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout successful');

        $this->assertNull(PersonalAccessToken::findToken($token->plainTextToken));
    }
}
