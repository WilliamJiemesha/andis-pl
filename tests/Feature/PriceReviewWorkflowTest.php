<?php

namespace Tests\Feature;

use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Models\PriceHistory;
use App\Models\PriceReviewTask;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_cost_creates_price_review_task(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $masterItem = MasterItem::factory()->create(['selling_price' => 500000]);

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'quantity' => 1,
            'cost_code' => 'CC-100',
            'master_item_id' => $masterItem->id,
        ]);

        $this->assertCount(0, PriceReviewTask::all());

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-11',
            'quantity' => 1,
            'cost_code' => 'CC-200',
            'master_item_id' => $masterItem->id,
        ]);

        $task = PriceReviewTask::query()->firstOrFail();

        $this->assertSame($masterItem->id, $task->master_item_id);
        $this->assertSame(PriceReviewTask::STATUS_OPEN, $task->status);
    }

    public function test_price_handler_can_update_selling_price_from_review_task(): void
    {
        $priceHandler = $this->createUserWithRole(Role::PRICE_HANDLER);
        $masterItem = MasterItem::factory()->create(['selling_price' => 500000]);
        $task = PriceReviewTask::query()->create([
            'master_item_id' => $masterItem->id,
            'status' => PriceReviewTask::STATUS_OPEN,
            'current_selling_price' => 500000,
        ]);

        $response = $this->actingAs($priceHandler)->post(route('price-review-tasks.review', $task), [
            'new_selling_price' => 650000,
            'review_note' => 'Margin adjusted',
        ]);

        $response->assertRedirect(route('price-review-tasks.show', $task));

        $task->refresh();
        $masterItem->refresh();
        $history = PriceHistory::query()->firstOrFail();

        $this->assertSame(PriceReviewTask::STATUS_REVIEWED, $task->status);
        $this->assertSame('650000.00', (string) $masterItem->selling_price);
        $this->assertSame('500000.00', (string) $history->previous_price);
        $this->assertSame('650000.00', (string) $history->new_price);
    }

    public function test_price_handler_can_keep_current_price(): void
    {
        $priceHandler = $this->createUserWithRole(Role::PRICE_HANDLER);
        $masterItem = MasterItem::factory()->create(['selling_price' => 500000]);
        $task = PriceReviewTask::query()->create([
            'master_item_id' => $masterItem->id,
            'status' => PriceReviewTask::STATUS_OPEN,
            'current_selling_price' => 500000,
        ]);

        $response = $this->actingAs($priceHandler)->post(route('price-review-tasks.review', $task), [
            'new_selling_price' => 500000,
            'review_note' => 'Current price still fine',
        ]);

        $response->assertRedirect(route('price-review-tasks.show', $task));
        $task->refresh();

        $this->assertSame(PriceReviewTask::STATUS_REVIEWED, $task->status);
        $this->assertCount(0, PriceHistory::all());
    }

    public function test_invoice_handler_cannot_access_price_review_pages(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $masterItem = MasterItem::factory()->create();
        $task = PriceReviewTask::query()->create([
            'master_item_id' => $masterItem->id,
            'status' => PriceReviewTask::STATUS_OPEN,
        ]);

        $response = $this->actingAs($invoiceHandler)->get(route('price-review-tasks.show', $task));

        $response->assertForbidden();
    }

    private function createUserWithRole(string $roleName): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }
}
