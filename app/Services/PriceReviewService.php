<?php

namespace App\Services;

use App\Models\CostHistory;
use App\Models\InvoiceEntry;
use App\Models\PriceReviewTask;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PriceReviewService
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function recordInvoiceCostChange(InvoiceEntry $invoiceEntry): void
    {
        if (! $invoiceEntry->master_item_id) {
            return;
        }

        if (blank($invoiceEntry->cost_code) && $invoiceEntry->decoded_cost_amount === null) {
            return;
        }

        DB::transaction(function () use ($invoiceEntry): void {
            $latestCost = CostHistory::query()
                ->where('master_item_id', $invoiceEntry->master_item_id)
                ->latest('recorded_at')
                ->latest('id')
                ->first();

            $costHistory = CostHistory::query()->create([
                'master_item_id' => $invoiceEntry->master_item_id,
                'invoice_entry_id' => $invoiceEntry->id,
                'vendor' => $invoiceEntry->vendor,
                'cost_code' => $invoiceEntry->cost_code,
                'decoded_cost_amount' => $invoiceEntry->decoded_cost_amount,
                'recorded_by' => $invoiceEntry->created_by,
                'recorded_at' => $invoiceEntry->created_at ?? now(),
            ]);

            $hasCostChanged = $latestCost !== null
                && (
                    (string) $latestCost->decoded_cost_amount !== (string) $invoiceEntry->decoded_cost_amount
                    || (string) $latestCost->cost_code !== (string) $invoiceEntry->cost_code
                );

            if ($hasCostChanged) {
                $task = PriceReviewTask::query()->create([
                    'master_item_id' => $invoiceEntry->master_item_id,
                    'invoice_entry_id' => $invoiceEntry->id,
                    'cost_history_id' => $costHistory->id,
                    'previous_cost_amount' => $latestCost->decoded_cost_amount,
                    'current_selling_price' => $invoiceEntry->masterItem?->selling_price,
                    'status' => PriceReviewTask::STATUS_OPEN,
                ]);

                $this->notificationService->notifyRoles(
                    [Role::ADMIN, Role::PRICE_HANDLER],
                    'price_review_created',
                    'Review Harga',
                    'Perubahan kode modal memerlukan review harga untuk '.($invoiceEntry->masterItem?->official_name ?? '-').'.',
                    route('price-review-tasks.show', $task),
                    PriceReviewTask::class,
                    $task->id,
                );
            }
        });
    }
}
