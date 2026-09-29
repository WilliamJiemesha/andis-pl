<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_page(): void
    {
        $admin = $this->createUserWithRole(Role::ADMIN);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSeeText(__('messages.user_management'));
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = $this->createUserWithRole(Role::ADMIN);
        $salesRole = Role::query()->where('name', Role::SALES_USER)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'password' => 'password',
            'roles' => [$salesRole->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $createdUser = User::query()->where('email', 'sales@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $createdUser->password));
        $this->assertTrue($createdUser->roles->contains('name', Role::SALES_USER));
    }

    public function test_non_admin_cannot_access_user_management_page(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);

        $response = $this->actingAs($invoiceHandler)->get(route('users.index'));

        $response->assertForbidden();
    }

    private function createUserWithRole(string $roleName): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }
}
