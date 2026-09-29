<?php

namespace App\Services;

use App\Models\CostHistory;
use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Models\PriceHistory;
use App\Models\PriceReviewTask;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ExternalItemSyncService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly IncomingCheckService $incomingCheckService,
    ) {
    }

    public function normalizePayload(array $payload): array
    {
        return [
            'barang' => MasterItem::normalizePart((string) ($payload['barang'] ?? '')),
            'merk' => MasterItem::normalizePart((string) ($payload['merk'] ?? '')),
            'tipe' => MasterItem::normalizePart((string) ($payload['tipe'] ?? '')),
            'official_name' => MasterItem::normalizePart((string) ($payload['official_name'] ?? '')),
            'alias' => MasterItem::normalizeAlias((string) ($payload['alias'] ?? '')),
            'sku_code' => MasterItem::normalizePart((string) ($payload['sku_code'] ?? '')),
            'cost_code' => MasterItem::normalizePart((string) ($payload['cost_code'] ?? '')),
            'selling_price' => $payload['selling_price'] ?? null,
            'selling_price_needs_review' => (bool) ($payload['selling_price_needs_review'] ?? false),
            'amount' => (int) ($payload['amount'] ?? $payload['jumlah'] ?? 0),
            'kode_supplier' => MasterItem::normalizePart((string) ($payload['kode_supplier'] ?? 'AVFP')),
        ];
    }

    public function findExistingItem(string $barang, string $merk, string $tipe): ?MasterItem
    {
        return MasterItem::query()
            ->where('barang', $barang)
            ->where('merk', $merk)
            ->where('tipe', $tipe)
            ->first();
    }

    public function search(string $barang, string $merk, string $tipe): ?MasterItem
    {
        $item = $this->findExistingItem($barang, $merk, $tipe);

        if ($item) {
            return $item;
        }

        $systemName = MasterItem::buildOfficialName($barang, $merk, $tipe);

        return MasterItem::query()
            ->where(function ($query) use ($systemName): void {
                $query->where('official_name', $systemName)
                    ->orWhere('alias_name', $systemName);
            })
            ->first();
    }

    public function sync(array $normalizedPayload, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($normalizedPayload, $actorId): array {
            $item = $this->findExistingItem(
                $normalizedPayload['barang'],
                $normalizedPayload['merk'],
                $normalizedPayload['tipe'],
            );

            if ($item) {
                $result = $this->updateExistingItem($item, $normalizedPayload, $actorId);
                $incomingEntry = $this->createIncomingEntryIfNeeded($item->fresh(), $normalizedPayload, $actorId);

                return [
                    'item' => $item->fresh(),
                    'exists' => true,
                    'action' => 'updated_existing',
                    'price_review_created' => $result['price_review_created'],
                    'selling_price_updated' => $result['selling_price_updated'],
                    'incoming_entry' => $incomingEntry,
                ];
            }

            $item = $this->createNewItem($normalizedPayload, $actorId);
            $priceReviewCreated = $this->processCostCode($item, $normalizedPayload['cost_code'] ?: null, $actorId, true, $normalizedPayload['kode_supplier'] ?: 'AVFP');
            if ($normalizedPayload['selling_price_needs_review']) {
                $priceReviewCreated = $this->createPriceReviewTask($item, null) || $priceReviewCreated;
            }
            $incomingEntry = $this->createIncomingEntryIfNeeded($item->fresh(), $normalizedPayload, $actorId);

            return [
                'item' => $item->fresh(),
                'exists' => false,
                'action' => 'created_new',
                'price_review_created' => $priceReviewCreated,
                'selling_price_updated' => false,
                'incoming_entry' => $incomingEntry,
            ];
        });
    }

    private function updateExistingItem(MasterItem $item, array $normalizedPayload, ?int $actorId): array
    {
        $updates = [];

        if (filled($normalizedPayload['sku_code']) && $normalizedPayload['sku_code'] !== $item->sku) {
            $updates['sku'] = $normalizedPayload['sku_code'];
        }

        if (filled($normalizedPayload['alias']) && $normalizedPayload['alias'] !== $item->alias_name) {
            $updates['alias_name'] = $normalizedPayload['alias'];
        }

        if ($updates !== []) {
            $updates['updated_by'] = $actorId;
            $item->update($updates);
            $item->refresh();
        }

        $priceReviewCreated = $this->processCostCode($item, $normalizedPayload['cost_code'] ?: null, $actorId, false, $normalizedPayload['kode_supplier'] ?: 'AVFP');
        $sellingPriceUpdated = $this->processSellingPrice($item->fresh(), $normalizedPayload, $actorId);

        if ($normalizedPayload['selling_price_needs_review'] && ! $priceReviewCreated) {
            $priceReviewCreated = $this->createPriceReviewTask($item->fresh(), null);
        }

        return [
            'price_review_created' => $priceReviewCreated,
            'selling_price_updated' => $sellingPriceUpdated,
        ];
    }

    private function createNewItem(array $normalizedPayload, ?int $actorId): MasterItem
    {
        $item = MasterItem::query()->create([
            'barang' => $normalizedPayload['barang'],
            'merk' => $normalizedPayload['merk'],
            'tipe' => $normalizedPayload['tipe'],
            'sku' => $normalizedPayload['sku_code'] ?: MasterItem::generateSku(),
            'alias_name' => MasterItem::normalizeAlias($normalizedPayload['alias']),
            'selling_price' => $normalizedPayload['selling_price'],
            'notes' => null,
            'is_active' => true,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        return $item;
    }

    private function processCostCode(MasterItem $item, ?string $costCode, ?int $actorId, bool $forceReview, string $vendor = 'AVFP'): bool
    {
        if (blank($costCode)) {
            return false;
        }

        $latestCost = CostHistory::query()
            ->where('master_item_id', $item->id)
            ->latest('recorded_at')
            ->latest('id')
            ->first();

        if (! $forceReview && $latestCost && $latestCost->cost_code === $costCode) {
            return false;
        }

        $costHistory = CostHistory::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => null,
            'vendor' => $vendor,
            'cost_code' => $costCode,
            'decoded_cost_amount' => null,
            'recorded_by' => $actorId,
            'recorded_at' => now(),
        ]);

        $task = PriceReviewTask::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => null,
            'cost_history_id' => $costHistory->id,
            'previous_cost_amount' => $latestCost?->decoded_cost_amount,
            'current_selling_price' => $item->selling_price,
            'status' => PriceReviewTask::STATUS_OPEN,
        ]);

        $this->notificationService->notifyRoles(
            [Role::ADMIN, Role::PRICE_HANDLER],
            'price_review_created',
            'Review Harga',
            'Master barang '.$item->official_name.' perlu review harga.',
            route('price-review-tasks.show', $task),
            PriceReviewTask::class,
            $task->id,
        );

        return true;
    }

    private function processSellingPrice(MasterItem $item, array $normalizedPayload, ?int $actorId): bool
    {
        if ($normalizedPayload['selling_price_needs_review'] || $normalizedPayload['selling_price'] === null) {
            return false;
        }

        $previousPrice = $item->selling_price;
        $newPrice = (float) $normalizedPayload['selling_price'];

        if ($previousPrice !== null && (float) $previousPrice === $newPrice) {
            return false;
        }

        $item->update([
            'selling_price' => $newPrice,
            'updated_by' => $actorId,
        ]);

        PriceHistory::query()->create([
            'master_item_id' => $item->id,
            'previous_price' => $previousPrice,
            'new_price' => $newPrice,
            'changed_by' => $actorId,
            'note' => 'Perubahan dari AVFP.',
            'effective_at' => now(),
        ]);

        $notificationType = $previousPrice !== null && $newPrice < (float) $previousPrice
            ? 'price_updated_down'
            : 'price_updated_up';

        $this->notificationService->notifyRoles(
            [Role::ADMIN, Role::SALES_USER, Role::PRICE_HANDLER],
            $notificationType,
            'Perubahan Harga',
            $notificationType === 'price_updated_down'
                ? 'Harga jual turun untuk '.$item->official_name.'.'
                : 'Harga jual naik untuk '.$item->official_name.'.',
            route('master-items.price-history', $item),
            MasterItem::class,
            $item->id,
        );

        return true;
    }

    private function createPriceReviewTask(MasterItem $item, ?CostHistory $costHistory): bool
    {
        $task = PriceReviewTask::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => null,
            'cost_history_id' => $costHistory?->id,
            'previous_cost_amount' => null,
            'current_selling_price' => $item->selling_price,
            'status' => PriceReviewTask::STATUS_OPEN,
        ]);

        $this->notificationService->notifyRoles(
            [Role::ADMIN, Role::PRICE_HANDLER],
            'price_review_created',
            'Review Harga',
            'Harga jual perlu review untuk '.$item->official_name.'.',
            route('price-review-tasks.show', $task),
            PriceReviewTask::class,
            $task->id,
        );

        return true;
    }

    private function createIncomingEntryIfNeeded(MasterItem $item, array $normalizedPayload, ?int $actorId): ?InvoiceEntry
    {
        $amount = (int) ($normalizedPayload['amount'] ?? 0);

        if ($amount < 1) {
            return null;
        }

        $incomingEntry = InvoiceEntry::query()->create([
            'invoice_number' => 'AVFP-'.now()->format('Ymd-Hisv'),
            'vendor' => $normalizedPayload['kode_supplier'] ?: 'AVFP',
            'invoice_date' => now()->toDateString(),
            'raw_item_name' => $item->alias_name ?: $item->official_name,
            'quantity' => $amount,
            'expected_quantity' => $amount,
            'cost_code' => $normalizedPayload['cost_code'] ?: null,
            'decoded_cost_amount' => null,
            'notes' => 'Kiriman AVFP',
            'master_item_id' => $item->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => $actorId,
        ]);

        $this->incomingCheckService->ensureTaskForInvoiceEntry($incomingEntry);

        return $incomingEntry->load('masterItem');
    }
}
