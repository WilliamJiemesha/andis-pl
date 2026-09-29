<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'barang',
    'merk',
    'tipe',
    'sku',
    'official_name',
    'alias_name',
    'selling_price',
    'notes',
    'is_active',
    'created_by',
    'updated_by',
])]
class MasterItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $masterItem): void {
            $masterItem->barang = self::normalizePart($masterItem->barang);
            $masterItem->merk = self::normalizePart($masterItem->merk);
            $masterItem->tipe = self::normalizePart($masterItem->tipe);
            $masterItem->sku = filled($masterItem->sku)
                ? self::normalizePart($masterItem->sku)
                : self::generateSku();
            $masterItem->official_name = self::buildOfficialName(
                $masterItem->barang,
                $masterItem->merk,
                $masterItem->tipe,
            );
            $masterItem->alias_name = self::normalizeAlias($masterItem->alias_name ?: $masterItem->official_name);
        });
    }

    public static function normalizePart(string $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }

    public static function buildOfficialName(string $barang, string $merk, string $tipe): string
    {
        return implode(' ', array_filter([
            self::normalizePart($barang),
            self::normalizePart($merk),
            self::normalizePart($tipe),
        ]));
    }

    public static function normalizeAlias(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    public static function generateSku(): string
    {
        $nextId = ((int) self::query()->max('id')) + 1;

        do {
            $sku = 'NB-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (self::query()->where('sku', $sku)->exists());

        return $sku;
    }

    public function displayName(): string
    {
        return $this->official_name;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function invoiceEntries(): HasMany
    {
        return $this->hasMany(InvoiceEntry::class);
    }

    public function costHistories(): HasMany
    {
        return $this->hasMany(CostHistory::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function priceReviewTasks(): HasMany
    {
        return $this->hasMany(PriceReviewTask::class);
    }

    public function incomingCheckTasks(): HasMany
    {
        return $this->hasMany(IncomingCheckTask::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ItemAlias::class);
    }
}
