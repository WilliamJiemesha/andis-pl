<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'master_item_id',
    'alias_name',
    'normalized_alias',
])]
class ItemAlias extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $alias): void {
            $alias->alias_name = MasterItem::normalizeAlias($alias->alias_name);
            $alias->normalized_alias = MasterItem::normalizePart($alias->alias_name);
        });
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }
}
