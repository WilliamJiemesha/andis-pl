<?php

namespace Database\Seeders;

use App\Models\ExternalApiToken;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExternalApiTokenSeeder extends Seeder
{
    public function run(): void
    {
        $plainTextToken = (string) env('NON_BUKU_EXTERNAL_API_TOKEN', 'non-buku-external-demo-token');
        $tokenName = (string) env('NON_BUKU_EXTERNAL_API_TOKEN_NAME', 'Integrasi External Default');
        $creatorId = User::query()->orderBy('id')->value('id');

        ExternalApiToken::query()->updateOrCreate(
            ['token_hash' => ExternalApiToken::hashToken($plainTextToken)],
            [
                'name' => $tokenName,
                'created_by' => $creatorId,
                'is_active' => true,
            ],
        );

        if ($this->command) {
            $this->command->info('External API token aktif: '.$plainTextToken);
        }
    }
}
