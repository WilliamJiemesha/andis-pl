<?php

namespace Tests\Feature;

use App\Models\IncomingCheckTask;
use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomingCheckWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_matched_invoice_with_expected_quantity_creates_incoming_check_task(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $masterItem = MasterItem::factory()->create();

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'raw_item_name' => 'GENSET A',
            'quantity' => 3,
            'cost_code' => 'CC-IC',
            'master_item_id' => $masterItem->id,
            'create_resolution_ticket' => '0',
        ]);

        $task = IncomingCheckTask::query()->firstOrFail();

        $this->assertSame(3, $task->expected_quantity);
        $this->assertSame(IncomingCheckTask::STATUS_PENDING, $task->status);
        $this->assertSame($masterItem->id, $task->master_item_id);
    }

    public function test_incoming_checker_can_mark_task_ok(): void
    {
        $checker = $this->createUserWithRole(Role::INCOMING_CHECKER);
        $task = $this->createPendingTask(5);

        $response = $this->actingAs($checker)->post(route('incoming-check-tasks.update', $task), [
            'checked_quantity' => 5,
            'notes' => 'All good',
        ]);

        $response->assertRedirect(route('incoming-check-tasks.show', $task));

        $task->refresh();

        $this->assertSame(IncomingCheckTask::STATUS_OK, $task->status);
        $this->assertSame(5, $task->checked_quantity);
        $this->assertNotNull($task->checked_at);
    }

    public function test_quick_confirm_returns_to_pending_list(): void
    {
        $admin = $this->createUserWithRole(Role::ADMIN);
        $task = $this->createPendingTask(5);

        $response = $this->actingAs($admin)->post(route('incoming-check-tasks.update', $task), [
            'checked_quantity' => 5,
            'notes' => '',
            'redirect_to' => 'index',
        ]);

        $response->assertRedirect(route('incoming-check-tasks.index', ['status' => IncomingCheckTask::STATUS_PENDING]));

        $this->assertSame(IncomingCheckTask::STATUS_OK, $task->fresh()->status);
    }

    public function test_incoming_checker_can_mark_task_mismatch(): void
    {
        $checker = $this->createUserWithRole(Role::INCOMING_CHECKER);
        $task = $this->createPendingTask(5);

        $response = $this->actingAs($checker)->post(route('incoming-check-tasks.update', $task), [
            'checked_quantity' => 4,
            'notes' => 'One unit missing',
        ]);

        $response->assertRedirect(route('incoming-check-tasks.show', $task));

        $task->refresh();

        $this->assertSame(IncomingCheckTask::STATUS_MISMATCH, $task->status);
        $this->assertSame(4, $task->checked_quantity);
    }

    public function test_sales_user_cannot_access_incoming_check_pages(): void
    {
        $salesUser = $this->createUserWithRole(Role::SALES_USER);
        $task = $this->createPendingTask(2);

        $response = $this->actingAs($salesUser)->get(route('incoming-check-tasks.show', $task));

        $response->assertForbidden();
    }

    public function test_incoming_check_list_can_filter_checked_tasks_by_date(): void
    {
        $checker = $this->createUserWithRole(Role::INCOMING_CHECKER);
        $checkedTask = $this->createPendingTask(2, '2026-06-10', IncomingCheckTask::STATUS_OK, 'GENSET CHECKED');
        $this->createPendingTask(2, '2026-06-11', IncomingCheckTask::STATUS_OK, 'GENSET OTHER DATE');
        $this->createPendingTask(2, '2026-06-10', IncomingCheckTask::STATUS_PENDING, 'GENSET PENDING');

        $response = $this->actingAs($checker)->get(route('incoming-check-tasks.index', [
            'status' => 'checked',
            'date' => '2026-06-10',
        ]));

        $response
            ->assertOk()
            ->assertSee($checkedTask->masterItem->official_name)
            ->assertDontSee($checkedTask->invoiceEntry->invoice_number)
            ->assertSee('2026-06-10')
            ->assertDontSee('GENSET OTHER DATE')
            ->assertDontSee('GENSET PENDING');
    }

    public function test_pending_list_ignores_date_filter(): void
    {
        $checker = $this->createUserWithRole(Role::INCOMING_CHECKER);
        $pendingTask = $this->createPendingTask(2, '2026-06-10', IncomingCheckTask::STATUS_PENDING, 'GENSET PENDING');

        $response = $this->actingAs($checker)->get(route('incoming-check-tasks.index', [
            'status' => 'pending',
            'date' => '2026-06-11',
        ]));

        $response
            ->assertOk()
            ->assertSee($pendingTask->masterItem->official_name)
            ->assertDontSee('type="date"', false);
    }

    private function createPendingTask(
        int $expectedQuantity,
        string $invoiceDate = '2026-06-10',
        string $status = IncomingCheckTask::STATUS_PENDING,
        string $rawItemName = 'GENSET B',
    ): IncomingCheckTask
    {
        $this->seed(RoleSeeder::class);

        $creator = User::factory()->create();
        $masterItem = MasterItem::factory()->create([
            'official_name' => mb_strtoupper($rawItemName),
        ]);
        $invoiceEntry = InvoiceEntry::query()->create([
            'invoice_number' => 'INV-PENDING-'.fake()->unique()->numerify('####'),
            'vendor' => 'PT Supplier',
            'invoice_date' => $invoiceDate,
            'raw_item_name' => $rawItemName,
            'quantity' => 1,
            'expected_quantity' => $expectedQuantity,
            'master_item_id' => $masterItem->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => $creator->id,
        ]);

        return IncomingCheckTask::query()->create([
            'invoice_entry_id' => $invoiceEntry->id,
            'master_item_id' => $masterItem->id,
            'expected_quantity' => $expectedQuantity,
            'checked_quantity' => $status === IncomingCheckTask::STATUS_PENDING ? null : $expectedQuantity,
            'status' => $status,
            'checked_at' => $status === IncomingCheckTask::STATUS_PENDING ? null : now(),
        ]);
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
