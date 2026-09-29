<?php

namespace Tests\Feature;

use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceEntryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_handler_can_create_matched_invoice_entry(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $masterItem = MasterItem::factory()->create();

        $response = $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'pt supplier',
            'invoice_date' => '2026-06-10',
            'quantity' => 2,
            'cost_code' => 'CC-01',
            'master_item_id' => $masterItem->id,
            'notes' => 'Matched directly',
        ]);

        $invoiceEntry = InvoiceEntry::query()->firstOrFail();

        $response->assertRedirect(route('invoice-entries.show', $invoiceEntry));
        $this->assertSame(InvoiceEntry::STATUS_MATCHED, $invoiceEntry->status);
        $this->assertSame($masterItem->id, $invoiceEntry->master_item_id);
        $this->assertSame('PT SUPPLIER', $invoiceEntry->vendor);
        $this->assertSame(2, $invoiceEntry->expected_quantity);
        $this->assertStringStartsWith('BM-', $invoiceEntry->invoice_number);
        $this->assertSame($masterItem->official_name, $invoiceEntry->raw_item_name);
    }

    public function test_invoice_handler_can_create_new_master_inline_when_no_match_exists(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);

        $response = $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'quantity' => 1,
            'cost_code' => 'CC-02',
            'notes' => '',
            'create_master_inline' => '1',
            'new_master_barang' => 'generator',
            'new_master_merk' => 'matari',
            'new_master_tipe' => 'mx99',
            'new_master_alias_name' => 'Generator Matari MX99 Portable',
        ]);

        $invoiceEntry = InvoiceEntry::query()->firstOrFail();
        $masterItem = MasterItem::query()->firstOrFail();

        $response->assertRedirect(route('invoice-entries.show', $invoiceEntry));
        $this->assertSame(InvoiceEntry::STATUS_MATCHED, $invoiceEntry->status);
        $this->assertSame($masterItem->id, $invoiceEntry->master_item_id);
        $this->assertSame('GENERATOR MATARI MX99', $masterItem->official_name);
        $this->assertSame('Generator Matari MX99 Portable', $masterItem->alias_name);
        $this->assertStringStartsWith('NB-', $masterItem->sku);
    }

    public function test_invoice_entry_requires_match_or_new_master(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);

        $response = $this->actingAs($invoiceHandler)
            ->from(route('invoice-entries.create'))
            ->post(route('invoice-entries.store'), [
                'vendor' => 'PT Supplier',
                'invoice_date' => '2026-06-10',
                'quantity' => 1,
                'cost_code' => 'CC-03',
                'notes' => '',
            ]);

        $response->assertRedirect(route('invoice-entries.create'));
        $response->assertSessionHasErrors('master_item_id');
    }

    public function test_sales_user_cannot_access_invoice_entry_create_page(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);

        $response = $this->actingAs($salesUser)->get(route('invoice-entries.create'));

        $response->assertForbidden();
    }

    public function test_sales_user_cannot_access_invoice_entry_index_or_detail(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $masterItem = MasterItem::factory()->create();
        $invoiceEntry = InvoiceEntry::query()->create([
            'invoice_number' => 'INV-005',
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'raw_item_name' => 'GENSET MATARI 4900',
            'quantity' => 1,
            'cost_code' => 'CC-05',
            'master_item_id' => $masterItem->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => $invoiceHandler->id,
        ]);

        $this->actingAs($salesUser)->get(route('invoice-entries.index'))->assertForbidden();
        $this->actingAs($salesUser)->get(route('invoice-entries.show', $invoiceEntry))->assertForbidden();
    }

    public function test_invoice_handler_cannot_see_decoded_cost_amount(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $masterItem = MasterItem::factory()->create();
        $invoiceEntry = InvoiceEntry::query()->create([
            'invoice_number' => 'INV-004',
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'raw_item_name' => 'GENSET MATARI 4900',
            'quantity' => 1,
            'expected_quantity' => 1,
            'cost_code' => 'CC-04',
            'decoded_cost_amount' => 100000,
            'master_item_id' => $masterItem->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => $invoiceHandler->id,
        ]);

        $response = $this->actingAs($invoiceHandler)->get(route('invoice-entries.show', $invoiceEntry));

        $response->assertOk();
        $response->assertSeeText('CC-04');
        $response->assertDontSeeText('100,000.00');
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
