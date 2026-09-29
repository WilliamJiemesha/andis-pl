<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    public function notifyRoles(array $roles, string $type, string $title, string $body, ?string $url = null, ?string $relatedType = null, ?int $relatedId = null): void
    {
        $users = User::query()
            ->whereHas('roles', fn ($builder) => $builder->whereIn('name', $roles))
            ->get();

        $this->notifyUsers($users, $type, $title, $body, $url, $relatedType, $relatedId);
    }

    public function notifyUsers(iterable $users, string $type, string $title, string $body, ?string $url = null, ?string $relatedType = null, ?int $relatedId = null): void
    {
        foreach ($users as $user) {
            AppNotification::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
            ]);
        }
    }

    public function adminAndPriceHandlerRoles(): array
    {
        return [Role::ADMIN, Role::PRICE_HANDLER];
    }
}
