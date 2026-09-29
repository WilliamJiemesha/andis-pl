<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::defaults() as $name => $label) {
            Role::query()->updateOrCreate(
                ['name' => $name],
                ['label' => $label],
            );
        }
    }
}
