<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_entry_id',
    'master_item_id',
    'expected_quantity',
    'checked_quantity',
    'status',
    'notes',
    'checked_by',
    'checked_at',
])]
class IncomingCheckTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_OK = 'ok';
    public const STATUS_MISMATCH = 'mismatch';

    protected function casts(): array
    {
        return [
            'expected_quantity' => 'integer',
            'checked_quantity' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function invoiceEntry(): BelongsTo
    {
        return $this->belongsTo(InvoiceEntry::class);
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
