<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'token_hash',
    'created_by',
    'last_used_at',
    'is_active',
])]
class ExternalApiToken extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
