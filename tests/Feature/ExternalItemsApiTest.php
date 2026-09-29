<?php

namespace Tests\Feature;

use App\Models\CostHistory;
use App\Models\ExternalApiToken;
use App\Models\IncomingCheckTask;
use App\Models\MasterItem;
use App\Models\PriceHistory;
use App\Models\PriceReviewTask;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalItemsApiTest extends TestCase
{
    use RefreshDatabase;

    private string $plainToken = 'external-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(AdminUserSeeder::class);

        ExternalApiToken::query()->create([
            'name' => 'Test Token',
            'token_hash' => ExternalApiToken::hashToken($this->plainToken),
            'created_by' => User::query()->value('id'),
            'is_active' => true,
        ]);
    }

    public function test_search_returns_existing_item(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'CHAINSAW',
            'merk' => 'STIHL',
            'tipe' => 'MS382',
            'sku' => 'NB-000123',
            'official_name' => 'CHAINSAW STIHL MS382',
            'alias_name' => 'Chainsaw Stihl MS382',
        ]);

        CostHistory::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => null,
            'vendor' => 'AJM',
            'cost_code' => 'IPAUD',
            'decoded_cost_amount' => null,
            'recorded_by' => null,
            'recorded_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/non-buku/items/search?barang=chainsaw&tipe=ms382&merk=stihl');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('exists', true)
            ->assertJsonPath('data.sku_code', 'NB-000123')
            ->assertJsonPath('data.alias', 'Chainsaw Stihl MS382')
            ->assertJsonPath('data.pc', 'IPAUD')
            ->assertJsonPath('data.selling_price_code', \App\Support\PriceCode::encode($item->selling_price))
            ->assertJsonMissingPath('data.aliases');
    }

    public function test_search_returns_not_found_response(): void
    {
        $this->getJson('/api/v1/non-buku/items/search?barang=chainsaw&tipe=ms382&merk=stihl')
            ->assertNotFound()
            ->assertJsonPath('exists', false)
            ->assertJsonPath('data.barang', 'CHAINSAW');
    }

    public function test_search_enforces_avfp_identifier_lengths(): void
    {
        $this->getJson('/api/v1/non-buku/items/search?barang=TOO-LONG-BARANG&tipe=TOO-LONG&merk=TOOLONG')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['barang', 'tipe', 'merk']);
    }

    public function test_sync_updates_existing_item_and_sku(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'CHAINSAW',
            'merk' => 'STIHL',
            'tipe' => 'MS382',
            'sku' => 'NB-000123',
            'official_name' => 'CHAINSAW STIHL MS382',
            'alias_name' => 'CHAINSAW STIHL MS382',
        ]);

        CostHistory::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => null,
            'vendor' => 'EXTERNAL API',
            'cost_code' => 'OLD01',
            'decoded_cost_amount' => null,
            'recorded_by' => null,
            'recorded_at' => now()->subDay(),
        ]);

        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'chainsaw',
                'tipe' => 'ms382',
                'merk' => 'stihl',
                'alias' => 'Chainsaw Stihl MS382',
                'official_name' => 'Nama Baru Tidak Boleh Ganti Master',
                'sku_code' => 'NB-000999',
                'cost_code' => 'NEW02',
            ])
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('action', 'updated_existing')
            ->assertJsonPath('data.sku_code', 'NB-000999')
            ->assertJsonPath('data.price_review_created', true);

        $this->assertDatabaseHas('master_items', [
            'id' => $item->id,
            'sku' => 'NB-000999',
            'alias_name' => 'Chainsaw Stihl MS382',
        ]);

        $this->assertDatabaseHas('cost_histories', [
            'master_item_id' => $item->id,
            'cost_code' => 'NEW02',
        ]);

        $this->assertDatabaseHas('price_review_tasks', [
            'master_item_id' => $item->id,
            'status' => PriceReviewTask::STATUS_OPEN,
        ]);
    }

    public function test_sync_creates_new_item_cost_history_and_notification(): void
    {
        $response = $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'chainsaw',
                'tipe' => 'ms382',
                'merk' => 'stihl',
                'alias' => 'Chainsaw Stihl MS382',
                'official_name' => 'CHAINSAW STIHL MS382',
                'sku_code' => 'NB-000456',
                'cost_code' => 'IPAUD',
                'selling_price_code' => 'NRPK',
            ]);

        $response->assertCreated()
            ->assertJsonPath('exists', false)
            ->assertJsonPath('action', 'created_new')
            ->assertJsonPath('data.sku_code', 'NB-000456')
            ->assertJsonPath('data.selling_price', 95000)
            ->assertJsonPath('data.selling_price_code', 'NRPK')
            ->assertJsonPath('data.price_review_created', true);

        $itemId = $response->json('data.id');

        $this->assertDatabaseHas('master_items', [
            'id' => $itemId,
            'barang' => 'CHAINSAW',
            'merk' => 'STIHL',
            'tipe' => 'MS382',
            'sku' => 'NB-000456',
            'alias_name' => 'Chainsaw Stihl MS382',
            'selling_price' => 95000,
        ]);

        $this->assertDatabaseHas('cost_histories', [
            'master_item_id' => $itemId,
            'vendor' => 'AVFP',
            'cost_code' => 'IPAUD',
        ]);

        $this->assertDatabaseHas('price_review_tasks', [
            'master_item_id' => $itemId,
            'status' => PriceReviewTask::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'type' => 'price_review_created',
            'title' => 'Review Harga',
        ]);
    }

    public function test_sync_requires_cost_code_when_creating_new_item(): void
    {
        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'chainsaw',
                'tipe' => 'ms382',
                'merk' => 'stihl',
                'alias' => 'Chainsaw Stihl MS382',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cost_code']);
    }

    public function test_sync_enforces_avfp_identifier_lengths(): void
    {
        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'TOO-LONG-BARANG',
                'tipe' => 'TOO-LONG',
                'merk' => 'TOOLONG',
                'alias' => 'Chainsaw Stihl MS382',
                'cost_code' => 'IPAUD',
                'selling_price_code' => 'NRPK',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['barang', 'tipe', 'merk']);
    }

    public function test_sync_requires_selling_price_when_creating_new_item(): void
    {
        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'chainsaw',
                'tipe' => 'ms382',
                'merk' => 'stihl',
                'alias' => 'Chainsaw Stihl MS382',
                'cost_code' => 'IPAUD',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selling_price']);
    }

    public function test_sync_from_avfp_updates_alias_and_creates_incoming_check_task(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'CHAINSAW',
            'merk' => 'STIHL',
            'tipe' => 'MS382',
            'sku' => 'NB-000123',
            'alias_name' => 'Old Alias',
            'selling_price' => 9558,
        ]);

        CostHistory::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => null,
            'vendor' => 'AJM',
            'cost_code' => 'OLDPC',
            'decoded_cost_amount' => null,
            'recorded_by' => null,
            'recorded_at' => now()->subDay(),
        ]);

        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'chainsaw',
                'tipe' => 'ms382',
                'merk' => 'stihl',
                'alias' => 'Chainsaw Stihl MS382 Farm Boss',
                'amount' => 3,
                'pc' => 'NEWPC',
                'selling_price_code' => 'NRRA',
                'kode_supplier' => 'AJMK',
            ])
            ->assertOk()
            ->assertJsonPath('data.alias', 'Chainsaw Stihl MS382 Farm Boss')
            ->assertJsonPath('data.pc', 'NEWPC')
            ->assertJsonPath('data.selling_price_code', 'NRRA')
            ->assertJsonPath('data.selling_price_updated', false)
            ->assertJsonPath('data.incoming_created', true)
            ->assertJsonPath('data.price_review_created', true);

        $this->assertDatabaseHas('master_items', [
            'id' => $item->id,
            'alias_name' => 'Chainsaw Stihl MS382 Farm Boss',
            'selling_price' => 9558,
        ]);

        $this->assertDatabaseHas('cost_histories', [
            'master_item_id' => $item->id,
            'vendor' => 'AJMK',
            'cost_code' => 'NEWPC',
        ]);

        $this->assertDatabaseHas('invoice_entries', [
            'master_item_id' => $item->id,
            'vendor' => 'AJMK',
            'quantity' => 3,
            'cost_code' => 'NEWPC',
        ]);

        $this->assertDatabaseHas('incoming_check_tasks', [
            'master_item_id' => $item->id,
            'expected_quantity' => 3,
            'status' => IncomingCheckTask::STATUS_PENDING,
        ]);
    }

    public function test_sync_from_avfp_updates_selling_price_when_code_changes(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'PUMP',
            'merk' => 'HONDA',
            'tipe' => 'WB20XT',
            'sku' => 'NB-000321',
            'selling_price' => 9558,
        ]);

        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'pump',
                'tipe' => 'wb20xt',
                'merk' => 'honda',
                'alias' => 'Honda WB20XT Pump',
                'amount' => 1,
                'pc' => 'PCNEW',
                'selling_price_code' => 'NRPK',
                'kode_supplier' => 'AJM',
            ])
            ->assertOk()
            ->assertJsonPath('data.selling_price', 95000)
            ->assertJsonPath('data.selling_price_code', 'NRPK')
            ->assertJsonPath('data.selling_price_updated', true)
            ->assertJsonPath('data.incoming_created', true);

        $this->assertDatabaseHas('master_items', [
            'id' => $item->id,
            'selling_price' => 95000,
        ]);

        $this->assertDatabaseHas('price_histories', [
            'master_item_id' => $item->id,
            'previous_price' => 9558,
            'new_price' => 95000,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'type' => 'price_updated_up',
            'title' => 'Perubahan Harga',
        ]);
    }

    public function test_sync_from_avfp_with_dash_price_creates_price_review_without_updating_price(): void
    {
        $item = MasterItem::factory()->create([
            'barang' => 'PUMP',
            'merk' => 'HONDA',
            'tipe' => 'WB20XT',
            'sku' => 'NB-000654',
            'selling_price' => 9558,
        ]);

        $this->postJson('/api/v1/non-buku/items/sync', [
                'barang' => 'pump',
                'tipe' => 'wb20xt',
                'merk' => 'honda',
                'alias' => 'Honda WB20XT Pump',
                'amount' => 1,
                'pc' => 'PCOLD',
                'selling_price_code' => '---',
                'kode_supplier' => 'AJM',
            ])
            ->assertOk()
            ->assertJsonPath('data.selling_price', 9558)
            ->assertJsonPath('data.selling_price_code', 'NRRA')
            ->assertJsonPath('data.selling_price_updated', false)
            ->assertJsonPath('data.price_review_created', true);

        $this->assertDatabaseHas('master_items', [
            'id' => $item->id,
            'selling_price' => 9558,
        ]);

        $this->assertDatabaseMissing('price_histories', [
            'master_item_id' => $item->id,
        ]);

        $this->assertDatabaseHas('price_review_tasks', [
            'master_item_id' => $item->id,
            'status' => PriceReviewTask::STATUS_OPEN,
        ]);
    }
}
