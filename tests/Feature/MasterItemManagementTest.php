<?php

namespace Tests\Feature;

use App\Models\MasterItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_master_item_list(): void
    {
        $user = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $item = MasterItem::factory()->create([
            'barang' => 'GENERATOR',
            'merk' => 'MATARI',
            'tipe' => 'MPG4900',
        ]);

        $response = $this->actingAs($user)->get(route('master-items.index'));

        $response->assertOk();
        $response->assertSeeText($item->official_name);
    }

    public function test_admin_can_create_master_item_and_official_name_is_generated(): void
    {
        $admin = $this->createUserWithRole(Role::ADMIN);

        $response = $this->actingAs($admin)->post(route('master-items.store'), [
            'barang' => 'generator',
            'merk' => 'matari',
            'tipe' => 'mpg4900',
            'alias_name' => 'Generator Matari MPG4900 Silent',
            'notes' => 'Unit baru',
        ]);

        $masterItem = MasterItem::query()->firstOrFail();

        $response->assertRedirect(route('master-items.show', $masterItem));
        $this->assertSame('GENERATOR', $masterItem->barang);
        $this->assertSame('MATARI', $masterItem->merk);
        $this->assertSame('MPG4900', $masterItem->tipe);
        $this->assertStringStartsWith('NB-', $masterItem->sku);
        $this->assertSame('GENERATOR MATARI MPG4900', $masterItem->official_name);
        $this->assertSame('Generator Matari MPG4900 Silent', $masterItem->alias_name);
        $this->assertNull($masterItem->selling_price);
    }

    public function test_duplicate_master_item_is_rejected(): void
    {
        $admin = $this->createUserWithRole(Role::ADMIN);

        MasterItem::factory()->create([
            'barang' => 'GENERATOR',
            'merk' => 'MATARI',
            'tipe' => 'MPG4900',
        ]);

        $response = $this->actingAs($admin)->from(route('master-items.create'))->post(route('master-items.store'), [
            'barang' => 'generator',
            'merk' => 'matari',
            'tipe' => 'mpg4900',
            'alias_name' => 'Generator Matari MPG4900',
            'notes' => '',
        ]);

        $response->assertRedirect(route('master-items.create'));
        $response->assertSessionHasErrors('barang');
        $this->assertCount(1, MasterItem::all());
    }

    public function test_sales_user_cannot_create_master_item(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);

        $response = $this->actingAs($salesUser)->get(route('master-items.create'));

        $response->assertForbidden();
    }

    public function test_sales_user_can_view_master_item_detail_but_not_master_item_list(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);
        $item = MasterItem::factory()->create([
            'barang' => 'GENERATOR',
            'merk' => 'MATARI',
            'tipe' => 'MPG4900',
        ]);

        $this->actingAs($salesUser)->get(route('master-items.index'))->assertForbidden();
        $this->actingAs($salesUser)->get(route('master-items.show', $item))->assertOk();
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
