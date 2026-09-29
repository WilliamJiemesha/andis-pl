<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'type',
    'title',
    'body',
    'url',
    'related_type',
    'related_id',
    'read_at',
])]
class AppNotification extends Model
{
    use HasFactory;

    public const TONE_NEUTRAL = 'neutral';
    public const TONE_SUCCESS = 'success';
    public const TONE_WARNING = 'warning';
    public const TONE_DANGER = 'danger';

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function tone(): string
    {
        return match ($this->type) {
            'price_updated_up', 'ticket_resolved', 'ticket_resolved_created_master' => self::TONE_SUCCESS,
            'price_updated_down' => self::TONE_WARNING,
            'price_review_created', 'incoming_mismatch', 'ticket_created' => self::TONE_DANGER,
            default => self::TONE_NEUTRAL,
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            'incoming_checked' => 'fa-solid fa-boxes-stacked',
            'incoming_mismatch' => 'fa-solid fa-triangle-exclamation',
            'price_review_created' => 'fa-solid fa-tag',
            'price_updated_up' => 'fa-solid fa-arrow-trend-up',
            'price_updated_down' => 'fa-solid fa-arrow-trend-down',
            'ticket_created' => 'fa-solid fa-circle-exclamation',
            'ticket_resolved', 'ticket_resolved_created_master' => 'fa-solid fa-circle-check',
            default => 'fa-solid fa-bell',
        };
    }

    public function requiresAction(): bool
    {
        return in_array($this->type, ['price_review_created', 'incoming_mismatch'], true);
    }

    public function actionLabel(): ?string
    {
        return match ($this->type) {
            'price_review_created' => 'Review',
            'incoming_mismatch' => 'Cek',
            default => null,
        };
    }

    public function resolvedUrl(): ?string
    {
        if (blank($this->url)) {
            return null;
        }

        if (Str::startsWith($this->url, ['/','#'])) {
            return $this->url;
        }

        $parts = parse_url($this->url);

        if ($parts === false) {
            return $this->url;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }
}
