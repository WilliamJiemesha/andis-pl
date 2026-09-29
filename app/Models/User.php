<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains(fn (Role $assignedRole) => $assignedRole->name === $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->contains(fn (Role $assignedRole) => in_array($assignedRole->name, $roles, true));
    }

    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        $this->load('roles');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function canViewCostCode(): bool
    {
        return $this->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER, Role::INVOICE_HANDLER]);
    }

    public function canViewDecodedCostAmount(): bool
    {
        return $this->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER]);
    }
}
