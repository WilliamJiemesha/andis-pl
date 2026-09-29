<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\MasterItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_change_notifies_price_handler(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $priceHandler = $this->createUserWithRole(Role::PRICE_HANDLER);
        $masterItem = MasterItem::factory()->create();

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'quantity' => 1,
            'cost_code' => 'CC-100',
            'master_item_id' => $masterItem->id,
        ]);

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-11',
            'quantity' => 1,
            'cost_code' => 'CC-200',
            'master_item_id' => $masterItem->id,
        ]);

        $this->assertCount(1, $priceHandler->notifications()->where('type', 'price_review_created')->get());
    }

    public function test_price_review_creation_notifies_price_handler(): void
    {
        $invoiceHandler = $this->createUserWithRole(Role::INVOICE_HANDLER);
        $priceHandler = $this->createUserWithRole(Role::PRICE_HANDLER);
        $masterItem = MasterItem::factory()->create();

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-10',
            'quantity' => 1,
            'cost_code' => 'CC-100',
            'master_item_id' => $masterItem->id,
        ]);

        $this->actingAs($invoiceHandler)->post(route('invoice-entries.store'), [
            'vendor' => 'PT Supplier',
            'invoice_date' => '2026-06-11',
            'quantity' => 1,
            'cost_code' => 'CC-200',
            'master_item_id' => $masterItem->id,
        ]);

        $this->assertTrue($priceHandler->notifications()->where('type', 'price_review_created')->exists());
    }

    public function test_notification_show_marks_item_as_read(): void
    {
        $user = $this->createUserWithRole(Role::ADMIN);
        $notification = AppNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'manual',
            'title' => 'Manual notification',
            'body' => 'Body',
        ]);

        $response = $this->actingAs($user)->get(route('notifications.show', $notification));

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $user = $this->createUserWithRole(Role::ADMIN);
        $otherUser = $this->createUserWithRole(Role::SALES_USER);
        $notification = AppNotification::query()->create([
            'user_id' => $otherUser->id,
            'type' => 'manual',
            'title' => 'Private',
            'body' => 'Body',
        ]);

        $response = $this->actingAs($user)->get(route('notifications.show', $notification));

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
