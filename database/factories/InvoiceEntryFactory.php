<?php

namespace Database\Factories;

use App\Models\InvoiceEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceEntry>
 */
class InvoiceEntryFactory extends Factory
{
    protected $model = InvoiceEntry::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('####'),
            'vendor' => fake()->company(),
            'invoice_date' => fake()->date(),
            'raw_item_name' => strtoupper(fake()->words(3, true)),
            'quantity' => fake()->numberBetween(1, 5),
            'expected_quantity' => fake()->numberBetween(1, 5),
            'cost_code' => 'CC-'.fake()->numerify('###'),
            'decoded_cost_amount' => fake()->randomFloat(2, 10000, 100000),
            'notes' => fake()->sentence(),
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => User::factory(),
        ];
    }
}
