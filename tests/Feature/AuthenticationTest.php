<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Non Buku Project');
    }

    public function test_admin_user_can_log_in(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::query()->where('name', Role::ADMIN)->firstOrFail());

        $response = $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
