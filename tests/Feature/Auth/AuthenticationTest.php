<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Jewellery Chit Admin')
            ->assertSee('admin, manager, or staff account');
    }

    public function test_admin_can_authenticate_using_the_login_screen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_manager_can_authenticate_using_the_login_screen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'manager@example.com')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_staff_can_authenticate_using_the_login_screen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'staff@example.com')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_user_without_panel_role_cannot_authenticate_using_the_login_screen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Staff');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
