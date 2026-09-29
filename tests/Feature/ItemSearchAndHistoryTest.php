<?php

namespace Tests\Feature;

use App\Models\CostHistory;
use App\Models\IncomingCheckTask;
use App\Models\MasterItem;
use App\Models\PriceHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemSearchAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_search_items_and_see_selling_price(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);
        $item = MasterItem::factory()->create([
            'barang' => 'GENERATOR',
            'merk' => 'MATARI',
            'tipe' => 'MPG4900',
            'selling_price' => 1500000,
        ]);
        PriceHistory::query()->create([
            'master_item_id' => $item->id,
            'previous_price' => 1400000,
            'new_price' => 1500000,
            'changed_by' => $salesUser->id,
            'note' => 'Updated',
            'effective_at' => now(),
        ]);
        CostHistory::query()->create([
            'master_item_id' => $item->id,
            'vendor' => 'PT SUPPLIER',
            'cost_code' => 'CC-01',
            'decoded_cost_amount' => 1200000,
            'recorded_by' => $salesUser->id,
            'recorded_at' => now(),
        ]);

        IncomingCheckTask::query()->create([
            'invoice_entry_id' => \App\Models\InvoiceEntry::factory()->create([
                'master_item_id' => $item->id,
                'status' => \App\Models\InvoiceEntry::STATUS_MATCHED,
            ])->id,
            'master_item_id' => $item->id,
            'expected_quantity' => 2,
            'checked_quantity' => 2,
            'status' => IncomingCheckTask::STATUS_OK,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($salesUser)->get(route('item-search.index', ['q' => 'MATARI']));

        $response->assertOk();
        $response->assertSeeText($item->official_name);
        $response->assertSeeText('1.500.000');
        $response->assertSeeText('PT SUPPLIER');
        $response->assertDontSeeText('CC-01');
    }

    public function test_incoming_checker_cannot_access_search_items_page(): void
    {
        $incomingChecker = $this->createUserWithRole(Role::INCOMING_CHECKER);

        $response = $this->actingAs($incomingChecker)->get(route('item-search.index'));

        $response->assertForbidden();
    }

    public function test_sales_user_can_view_price_history_but_not_cost_history(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);
        $item = MasterItem::factory()->create();

        PriceHistory::query()->create([
            'master_item_id' => $item->id,
            'previous_price' => 100000,
            'new_price' => 125000,
            'changed_by' => $salesUser->id,
            'note' => 'Updated',
            'effective_at' => now(),
        ]);

        $priceResponse = $this->actingAs($salesUser)->get(route('master-items.price-history', $item));
        $priceResponse->assertOk();
        $priceResponse->assertSeeText('Rp 125.000');

        $costResponse = $this->actingAs($salesUser)->get(route('master-items.cost-history', $item));
        $costResponse->assertForbidden();
    }

    public function test_price_handler_can_view_cost_history(): void
    {
        $priceHandler = $this->createUserWithRole(Role::PRICE_HANDLER);
        $item = MasterItem::factory()->create();

        CostHistory::query()->create([
            'master_item_id' => $item->id,
            'vendor' => 'PT Supplier',
            'cost_code' => 'CC-500',
            'decoded_cost_amount' => 90000,
            'recorded_by' => $priceHandler->id,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($priceHandler)->get(route('master-items.cost-history', $item));

        $response->assertOk();
        $response->assertSeeText('CC-500');
        $response->assertDontSeeText('90,000.00');
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
