<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('name', Role::ADMIN)->firstOrFail();

        $users = [
            ['name' => 'Sri', 'email' => 'sri@example.com'],
            ['name' => 'Wendy', 'email' => 'wendy@example.com'],
            ['name' => 'William', 'email' => 'william@example.com'],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                ],
            );

            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
