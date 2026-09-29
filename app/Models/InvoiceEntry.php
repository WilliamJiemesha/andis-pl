<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_number',
    'vendor',
    'invoice_date',
    'raw_item_name',
    'quantity',
    'expected_quantity',
    'cost_code',
    'decoded_cost_amount',
    'notes',
    'master_item_id',
    'status',
    'created_by',
])]
class InvoiceEntry extends Model
{
    use HasFactory;

    public const STATUS_MATCHED = 'matched';
    public const STATUS_NEEDS_RESOLUTION = 'needs_resolution';

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'quantity' => 'integer',
            'expected_quantity' => 'integer',
            'decoded_cost_amount' => 'decimal:2',
        ];
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolutionTicket(): HasOne
    {
        return $this->hasOne(ItemResolutionTicket::class);
    }

    public function costHistories(): HasMany
    {
        return $this->hasMany(CostHistory::class);
    }

    public function priceReviewTasks(): HasMany
    {
        return $this->hasMany(PriceReviewTask::class);
    }

    public function incomingCheckTask(): HasOne
    {
        return $this->hasOne(IncomingCheckTask::class);
    }
}
