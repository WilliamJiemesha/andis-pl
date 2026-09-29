<?php

namespace Tests\Feature;

use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_create_master_item(): void
    {
        $response = $this->postJson('/api/v1/master-items', [
            'barang' => 'generator',
            'merk' => 'matari',
            'tipe' => 'mpg4900',
            'alias_name' => 'Generator Matari MPG4900 Silent',
            'notes' => 'API item',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.alias_name', 'Generator Matari MPG4900 Silent')
            ->assertJsonPath('data.system_name', 'GENERATOR MATARI MPG4900');

        $this->assertDatabaseHas('master_items', [
            'official_name' => 'GENERATOR MATARI MPG4900',
            'alias_name' => 'Generator Matari MPG4900 Silent',
        ]);
    }

    public function test_api_can_receive_incoming_item_for_existing_identifier_without_sku(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'GENERATOR',
            'merk' => 'MATARI',
            'tipe' => 'MPG4900',
            'sku' => 'GEN-MAT-MPG4900',
        ]);

        $response = $this->postJson('/api/v1/incoming-items', [
            'tanggal_masuk' => '2026-06-11',
            'kode_supplier' => 'ajm',
            'barang' => 'generator',
            'merk' => 'matari',
            'tipe' => 'mpg4900',
            'alias_name' => 'Generator Matari MPG4900 New Alias',
            'jumlah' => 2,
            'kode_modal' => 'mdl-gen-777',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sku', $item->sku)
            ->assertJsonPath('data.alias_name', 'Generator Matari MPG4900 New Alias');

        $this->assertDatabaseHas('master_items', [
            'id' => $item->id,
            'alias_name' => 'Generator Matari MPG4900 New Alias',
        ]);
    }

    public function test_api_can_receive_incoming_item_for_existing_sku(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'GENERATOR',
            'merk' => 'MATARI',
            'tipe' => 'MPG4900',
            'sku' => 'GEN-MAT-MPG4900',
        ]);

        $response = $this->postJson('/api/v1/incoming-items', [
            'tanggal_masuk' => '2026-06-11',
            'kode_supplier' => 'ajm',
            'sku' => 'gen-mat-mpg4900',
            'barang' => 'generator',
            'merk' => 'matari',
            'tipe' => 'mpg4900',
            'jumlah' => 2,
            'kode_modal' => 'mdl-gen-777',
            'catatan' => 'API incoming',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sku', $item->sku)
            ->assertJsonPath('data.kode_supplier', 'AJM');

        $this->assertDatabaseHas('invoice_entries', [
            'master_item_id' => $item->id,
            'vendor' => 'AJM',
            'quantity' => 2,
            'cost_code' => 'MDL-GEN-777',
        ]);

        $this->assertInstanceOf(InvoiceEntry::class, InvoiceEntry::query()->first());
    }
}
