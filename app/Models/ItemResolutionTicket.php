<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_entry_id',
    'raw_item_name',
    'vendor',
    'status',
    'created_by',
    'resolved_by',
    'resolved_master_item_id',
    'resolution_type',
    'resolution_note',
    'resolved_at',
])]
class ItemResolutionTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const RESOLUTION_LINK_EXISTING = 'linked_existing';
    public const RESOLUTION_CREATE_NEW = 'created_new';

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function invoiceEntry(): BelongsTo
    {
        return $this->belongsTo(InvoiceEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resolvedMasterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class, 'resolved_master_item_id');
    }
}
