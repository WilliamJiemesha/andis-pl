<?php

namespace Database\Factories;

use App\Models\MasterItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterItem>
 */
class MasterItemFactory extends Factory
{
    protected $model = MasterItem::class;

    public function definition(): array
    {
        $barang = fake()->randomElement(['GENERATOR', 'CHAINSAW', 'PUMP']);
        $merk = fake()->randomElement(['MATARI', 'STIHL', 'HONDA']);
        $tipe = strtoupper(fake()->bothify('??###'));

        return [
            'barang' => $barang,
            'merk' => $merk,
            'tipe' => $tipe,
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'official_name' => MasterItem::buildOfficialName($barang, $merk, $tipe),
            'alias_name' => MasterItem::buildOfficialName($barang, $merk, $tipe),
            'selling_price' => fake()->randomFloat(2, 1000, 5000),
            'notes' => fake()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
