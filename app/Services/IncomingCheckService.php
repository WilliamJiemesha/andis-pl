<?php

namespace App\Services;

use App\Models\IncomingCheckTask;
use App\Models\InvoiceEntry;

class IncomingCheckService
{
    public function ensureTaskForInvoiceEntry(InvoiceEntry $invoiceEntry): void
    {
        if (! $invoiceEntry->master_item_id || ! $invoiceEntry->expected_quantity) {
            return;
        }

        IncomingCheckTask::query()->updateOrCreate(
            ['invoice_entry_id' => $invoiceEntry->id],
            [
                'master_item_id' => $invoiceEntry->master_item_id,
                'expected_quantity' => $invoiceEntry->expected_quantity,
                'status' => IncomingCheckTask::STATUS_PENDING,
            ],
        );
    }
}
