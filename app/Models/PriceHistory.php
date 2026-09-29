<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'master_item_id',
    'previous_price',
    'new_price',
    'changed_by',
    'note',
    'effective_at',
])]
class PriceHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'previous_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'effective_at' => 'datetime',
        ];
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
