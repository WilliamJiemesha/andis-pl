<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label'])]
class Role extends Model
{
    public const ADMIN = 'admin';
    public const INVOICE_HANDLER = 'invoice_handler';
    public const PRICE_HANDLER = 'price_handler';
    public const INCOMING_CHECKER = 'incoming_checker';
    public const SALES_USER = 'sales_user';

    public static function defaults(): array
    {
        return [
            self::ADMIN => 'Admin',
            self::INVOICE_HANDLER => 'Invoice Handler',
            self::PRICE_HANDLER => 'Price Handler',
            self::INCOMING_CHECKER => 'Incoming Checker',
            self::SALES_USER => 'Sales User',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
