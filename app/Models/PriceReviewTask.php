<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'master_item_id',
    'invoice_entry_id',
    'cost_history_id',
    'previous_cost_amount',
    'current_selling_price',
    'status',
    'review_note',
    'reviewed_by',
    'reviewed_at',
])]
class PriceReviewTask extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_REVIEWED = 'reviewed';

    protected function casts(): array
    {
        return [
            'previous_cost_amount' => 'decimal:2',
            'current_selling_price' => 'decimal:2',
            'reviewed_at' => 'datetime',
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

    public function costHistory(): BelongsTo
    {
        return $this->belongsTo(CostHistory::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
