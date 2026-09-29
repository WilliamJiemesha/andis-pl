<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'master_item_id',
    'invoice_entry_id',
    'vendor',
    'cost_code',
    'decoded_cost_amount',
    'recorded_by',
    'recorded_at',
])]
class CostHistory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'decoded_cost_amount' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function invoiceEntry(): BelongsTo
    {
        return $this->belongsTo(InvoiceEntry::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
