<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\CostHistory;
use App\Models\IncomingCheckTask;
use App\Models\InvoiceEntry;
use App\Models\ItemResolutionTicket;
use App\Models\MasterItem;
use App\Models\PriceHistory;
use App\Models\PriceReviewTask;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        if (MasterItem::query()->exists()) {
            return;
        }

        $users = $this->seedUsersAndRoles();

        $items = $this->seedMasterItems($users);
        $entries = $this->seedIncomingItems($users, $items);

        $this->seedItemResolutionTickets($users, $items, $entries);
        $this->seedIncomingChecks($users, $entries);
        $this->seedPriceReviewTasks($users, $items, $entries);
        $this->seedNotifications($users, $items, $entries);
    }

    private function seedUsersAndRoles(): array
    {
        $users = [
            'sri' => User::query()->where('email', 'sri@example.com')->firstOrFail(),
            'wendy' => User::query()->where('email', 'wendy@example.com')->firstOrFail(),
            'william' => User::query()->where('email', 'william@example.com')->firstOrFail(),
            'rina' => User::query()->updateOrCreate(
                ['email' => 'rina@example.com'],
                ['name' => 'Rina', 'password' => 'password'],
            ),
            'bagas' => User::query()->updateOrCreate(
                ['email' => 'bagas@example.com'],
                ['name' => 'Bagas', 'password' => 'password'],
            ),
        ];

        $roles = Role::query()->get()->keyBy('name');

        $users['sri']->roles()->syncWithoutDetaching([$roles[Role::PRICE_HANDLER]->id]);
        $users['wendy']->roles()->syncWithoutDetaching([$roles[Role::INVOICE_HANDLER]->id]);
        $users['william']->roles()->syncWithoutDetaching([$roles[Role::INCOMING_CHECKER]->id]);
        $users['rina']->roles()->sync([$roles[Role::SALES_USER]->id]);
        $users['bagas']->roles()->sync([$roles[Role::PRICE_HANDLER]->id]);

        return $users;
    }

    private function seedMasterItems(array $users): Collection
    {
        $seedItems = [
            ['barang' => 'GENERATOR', 'merk' => 'MATARI', 'tipe' => 'MPG4900', 'sku' => 'GEN-MAT-MPG4900', 'alias_name' => 'Generator Matari MPG4900 Silent Portable', 'selling_price' => 8450000, 'notes' => 'Best seller for portable field work.'],
            ['barang' => 'GENERATOR', 'merk' => 'YAMAHA', 'tipe' => 'EF2600', 'sku' => 'GEN-YAM-EF2600', 'alias_name' => 'Generator Yamaha EF2600 Compact', 'selling_price' => 12950000, 'notes' => 'Quiet unit for villas and offices.'],
            ['barang' => 'CHAINSAW', 'merk' => 'STIHL', 'tipe' => 'MS382', 'sku' => 'CHN-STI-MS382', 'alias_name' => 'Chainsaw Stihl MS382', 'selling_price' => 6350000, 'notes' => 'Regular logging and estate use.'],
            ['barang' => 'WATER PUMP', 'merk' => 'HONDA', 'tipe' => 'WB20XT', 'sku' => 'PMP-HON-WB20XT', 'alias_name' => 'Water Pump Honda WB20XT', 'selling_price' => 4985000, 'notes' => 'Fast-moving irrigation item.'],
            ['barang' => 'AIR COMPRESSOR', 'merk' => 'SWAN', 'tipe' => 'SVP202', 'sku' => 'CMP-SWA-SVP202', 'alias_name' => 'Air Compressor Swan SVP202', 'selling_price' => 18750000, 'notes' => 'Workshop standard stock.'],
            ['barang' => 'WELDING MACHINE', 'merk' => 'LAKONI', 'tipe' => 'BASIC450', 'sku' => 'WLD-LAK-BASIC450', 'alias_name' => 'Welding Machine Lakoni Basic450', 'selling_price' => 2895000, 'notes' => 'Entry-level welding package.'],
            ['barang' => 'CONCRETE CUTTER', 'merk' => 'MAKITA', 'tipe' => 'EK6101', 'sku' => 'CTR-MAK-EK6101', 'alias_name' => 'Concrete Cutter Makita EK6101', 'selling_price' => 16400000, 'notes' => 'Project-only item.'],
        ];

        return collect($seedItems)->map(function (array $itemData, int $index) use ($users) {
            $createdAt = Carbon::now()->subDays(30 - ($index * 2));

            $item = MasterItem::query()->create([
                ...$itemData,
                'official_name' => MasterItem::buildOfficialName(
                    $itemData['barang'],
                    $itemData['merk'],
                    $itemData['tipe'],
                ),
                'is_active' => true,
                'created_by' => $users['sri']->id,
                'updated_by' => $users['sri']->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            PriceHistory::query()->create([
                'master_item_id' => $item->id,
                'previous_price' => null,
                'new_price' => $itemData['selling_price'] - 250000,
                'changed_by' => $users['sri']->id,
                'note' => 'Initial seeded baseline.',
                'effective_at' => $createdAt->copy()->addDay(),
            ]);

            PriceHistory::query()->create([
                'master_item_id' => $item->id,
                'previous_price' => $itemData['selling_price'] - 250000,
                'new_price' => $itemData['selling_price'],
                'changed_by' => $users['bagas']->id,
                'note' => 'Adjusted after supplier update.',
                'effective_at' => $createdAt->copy()->addDays(10),
            ]);

            return $item;
        })->keyBy('official_name');
    }

    private function seedIncomingItems(array $users, Collection $items): array
    {
        $rows = [];

        $rows['matari_old'] = $this->createMatchedEntry(
            users: $users,
            item: $items['GENERATOR MATARI MPG4900'],
            invoiceNumber: 'BM-20260603-090000001',
            vendor: 'AJM',
            date: Carbon::parse('2026-06-03'),
            rawName: 'GENSET MATARI MPG 4900',
            quantity: 2,
            costCode: 'MDL-GEN-742',
            decodedCost: 6980000,
            notes: 'Morning incoming from Surabaya.',
        );

        $rows['matari_new'] = $this->createMatchedEntry(
            users: $users,
            item: $items['GENERATOR MATARI MPG4900'],
            invoiceNumber: 'BM-20260610-091500221',
            vendor: 'AJM',
            date: Carbon::parse('2026-06-10'),
            rawName: 'GENSET MATARI MPG4900 UNIT',
            quantity: 3,
            costCode: 'MDL-GEN-775',
            decodedCost: 7340000,
            notes: 'Cost moved up this morning.',
        );

        $rows['stihl'] = $this->createMatchedEntry(
            users: $users,
            item: $items['CHAINSAW STIHL MS382'],
            invoiceNumber: 'BM-20260610-101000222',
            vendor: 'ATAK',
            date: Carbon::parse('2026-06-10'),
            rawName: 'CHAINSAW STIHL 382',
            quantity: 4,
            costCode: 'MDL-CHN-211',
            decodedCost: 5115000,
            notes: 'Pending warehouse count.',
        );

        $rows['pump'] = $this->createMatchedEntry(
            users: $users,
            item: $items['WATER PUMP HONDA WB20XT'],
            invoiceNumber: 'BM-20260609-141500198',
            vendor: 'PMS',
            date: Carbon::parse('2026-06-09'),
            rawName: 'POMPA HONDA WB20XT',
            quantity: 5,
            costCode: 'MDL-PMP-431',
            decodedCost: 3880000,
            notes: 'Already checked, one issue logged.',
        );

        $rows['lakoni'] = $this->createMatchedEntry(
            users: $users,
            item: $items['WELDING MACHINE LAKONI BASIC450'],
            invoiceNumber: 'BM-20260608-131000176',
            vendor: 'AM',
            date: Carbon::parse('2026-06-08'),
            rawName: 'MESIN LAS LAKONI BASIC 450',
            quantity: 6,
            costCode: 'MDL-WLD-118',
            decodedCost: 2140000,
            notes: 'Price held after review.',
        );

        $rows['yamaha'] = $this->createMatchedEntry(
            users: $users,
            item: $items['GENERATOR YAMAHA EF2600'],
            invoiceNumber: 'BM-20260610-120500325',
            vendor: 'AJMK',
            date: Carbon::parse('2026-06-10'),
            rawName: 'GENSET YAMAHA EF2600',
            quantity: 2,
            costCode: 'MDL-GEN-812',
            decodedCost: 10800000,
            notes: 'Fresh stock for showroom request.',
        );

        $rows['compressor'] = $this->createMatchedEntry(
            users: $users,
            item: $items['AIR COMPRESSOR SWAN SVP202'],
            invoiceNumber: 'BM-20260609-154500205',
            vendor: 'PMS',
            date: Carbon::parse('2026-06-09'),
            rawName: 'KOMPRESOR SWAN SVP202',
            quantity: 2,
            costCode: 'MDL-CMP-552',
            decodedCost: 15150000,
            notes: 'Workshop preorder stock.',
        );

        $rows['makita'] = $this->createMatchedEntry(
            users: $users,
            item: $items['CONCRETE CUTTER MAKITA EK6101'],
            invoiceNumber: 'BM-20260607-104500151',
            vendor: 'ATAK',
            date: Carbon::parse('2026-06-07'),
            rawName: 'CUTTER MAKITA EK6101',
            quantity: 1,
            costCode: 'MDL-CTR-044',
            decodedCost: 13950000,
            notes: 'Project allocation unit.',
        );

        $rows['unresolved_cutting'] = InvoiceEntry::query()->create([
            'invoice_number' => 'BM-20260610-111500301',
            'vendor' => 'ATAK',
            'invoice_date' => Carbon::parse('2026-06-10'),
            'raw_item_name' => 'CUTTING SAW MAKITA BESAR 14 INCH',
            'quantity' => 1,
            'expected_quantity' => 1,
            'cost_code' => 'MDL-UNK-900',
            'decoded_cost_amount' => null,
            'notes' => 'Needs master item review before sales can use it.',
            'master_item_id' => null,
            'status' => InvoiceEntry::STATUS_NEEDS_RESOLUTION,
            'created_by' => $users['wendy']->id,
            'created_at' => Carbon::parse('2026-06-10 11:15:00'),
            'updated_at' => Carbon::parse('2026-06-10 11:15:00'),
        ]);

        $rows['unresolved_compressor'] = InvoiceEntry::query()->create([
            'invoice_number' => 'BM-20260610-114000312',
            'vendor' => 'PMS',
            'invoice_date' => Carbon::parse('2026-06-10'),
            'raw_item_name' => 'KOMPRESOR SWAN 2HP BESI',
            'quantity' => 2,
            'expected_quantity' => 2,
            'cost_code' => 'MDL-UNK-901',
            'decoded_cost_amount' => null,
            'notes' => 'Likely existing item but naming is messy.',
            'master_item_id' => null,
            'status' => InvoiceEntry::STATUS_NEEDS_RESOLUTION,
            'created_by' => $users['wendy']->id,
            'created_at' => Carbon::parse('2026-06-10 11:40:00'),
            'updated_at' => Carbon::parse('2026-06-10 11:40:00'),
        ]);

        $rows['unresolved_generator'] = InvoiceEntry::query()->create([
            'invoice_number' => 'BM-20260610-133000390',
            'vendor' => 'AJMK',
            'invoice_date' => Carbon::parse('2026-06-10'),
            'raw_item_name' => 'GENSET YAMAHA SILENT KECIL',
            'quantity' => 1,
            'expected_quantity' => 1,
            'cost_code' => 'MDL-UNK-915',
            'decoded_cost_amount' => null,
            'notes' => 'Sales asked for urgent confirmation.',
            'master_item_id' => null,
            'status' => InvoiceEntry::STATUS_NEEDS_RESOLUTION,
            'created_by' => $users['wendy']->id,
            'created_at' => Carbon::parse('2026-06-10 13:30:00'),
            'updated_at' => Carbon::parse('2026-06-10 13:30:00'),
        ]);

        return $rows;
    }

    private function seedItemResolutionTickets(array $users, Collection $items, array $entries): void
    {
        ItemResolutionTicket::query()->create([
            'invoice_entry_id' => $entries['unresolved_cutting']->id,
            'raw_item_name' => $entries['unresolved_cutting']->raw_item_name,
            'vendor' => $entries['unresolved_cutting']->vendor,
            'status' => ItemResolutionTicket::STATUS_OPEN,
            'created_by' => $users['wendy']->id,
            'created_at' => Carbon::parse('2026-06-10 11:16:00'),
            'updated_at' => Carbon::parse('2026-06-10 11:16:00'),
        ]);

        ItemResolutionTicket::query()->create([
            'invoice_entry_id' => $entries['unresolved_compressor']->id,
            'raw_item_name' => $entries['unresolved_compressor']->raw_item_name,
            'vendor' => $entries['unresolved_compressor']->vendor,
            'status' => ItemResolutionTicket::STATUS_OPEN,
            'created_by' => $users['wendy']->id,
            'created_at' => Carbon::parse('2026-06-10 11:41:00'),
            'updated_at' => Carbon::parse('2026-06-10 11:41:00'),
        ]);

        ItemResolutionTicket::query()->create([
            'invoice_entry_id' => $entries['unresolved_generator']->id,
            'raw_item_name' => $entries['unresolved_generator']->raw_item_name,
            'vendor' => $entries['unresolved_generator']->vendor,
            'status' => ItemResolutionTicket::STATUS_OPEN,
            'created_by' => $users['wendy']->id,
            'created_at' => Carbon::parse('2026-06-10 13:31:00'),
            'updated_at' => Carbon::parse('2026-06-10 13:31:00'),
        ]);
    }

    private function seedIncomingChecks(array $users, array $entries): void
    {
        IncomingCheckTask::query()->create([
            'invoice_entry_id' => $entries['stihl']->id,
            'master_item_id' => $entries['stihl']->master_item_id,
            'expected_quantity' => 4,
            'status' => IncomingCheckTask::STATUS_PENDING,
            'created_at' => Carbon::parse('2026-06-10 10:15:00'),
            'updated_at' => Carbon::parse('2026-06-10 10:15:00'),
        ]);

        IncomingCheckTask::query()->create([
            'invoice_entry_id' => $entries['pump']->id,
            'master_item_id' => $entries['pump']->master_item_id,
            'expected_quantity' => 5,
            'checked_quantity' => 4,
            'status' => IncomingCheckTask::STATUS_MISMATCH,
            'notes' => 'One unit still on the truck manifest.',
            'checked_by' => $users['william']->id,
            'checked_at' => Carbon::parse('2026-06-09 16:10:00'),
            'created_at' => Carbon::parse('2026-06-09 14:20:00'),
            'updated_at' => Carbon::parse('2026-06-09 16:10:00'),
        ]);

        IncomingCheckTask::query()->create([
            'invoice_entry_id' => $entries['lakoni']->id,
            'master_item_id' => $entries['lakoni']->master_item_id,
            'expected_quantity' => 6,
            'checked_quantity' => 6,
            'status' => IncomingCheckTask::STATUS_OK,
            'notes' => 'Ready for floor display.',
            'checked_by' => $users['william']->id,
            'checked_at' => Carbon::parse('2026-06-08 15:30:00'),
            'created_at' => Carbon::parse('2026-06-08 13:30:00'),
            'updated_at' => Carbon::parse('2026-06-08 15:30:00'),
        ]);

        IncomingCheckTask::query()->create([
            'invoice_entry_id' => $entries['yamaha']->id,
            'master_item_id' => $entries['yamaha']->master_item_id,
            'expected_quantity' => 2,
            'status' => IncomingCheckTask::STATUS_PENDING,
            'created_at' => Carbon::parse('2026-06-10 12:20:00'),
            'updated_at' => Carbon::parse('2026-06-10 12:20:00'),
        ]);
    }

    private function seedPriceReviewTasks(array $users, Collection $items, array $entries): void
    {
        $matariCost = CostHistory::query()
            ->where('invoice_entry_id', $entries['matari_new']->id)
            ->firstOrFail();

        PriceReviewTask::query()->create([
            'master_item_id' => $items['GENERATOR MATARI MPG4900']->id,
            'invoice_entry_id' => $entries['matari_new']->id,
            'cost_history_id' => $matariCost->id,
            'previous_cost_amount' => 6980000,
            'current_selling_price' => 8450000,
            'status' => PriceReviewTask::STATUS_OPEN,
            'created_at' => Carbon::parse('2026-06-10 09:25:00'),
            'updated_at' => Carbon::parse('2026-06-10 09:25:00'),
        ]);

        $lakoniCost = CostHistory::query()
            ->where('invoice_entry_id', $entries['lakoni']->id)
            ->firstOrFail();

        PriceReviewTask::query()->create([
            'master_item_id' => $items['WELDING MACHINE LAKONI BASIC450']->id,
            'invoice_entry_id' => $entries['lakoni']->id,
            'cost_history_id' => $lakoniCost->id,
            'previous_cost_amount' => 2190000,
            'current_selling_price' => 2895000,
            'status' => PriceReviewTask::STATUS_REVIEWED,
            'review_note' => 'Cost dropped but current floor price still acceptable this week.',
            'reviewed_by' => $users['sri']->id,
            'reviewed_at' => Carbon::parse('2026-06-08 17:00:00'),
            'created_at' => Carbon::parse('2026-06-08 14:00:00'),
            'updated_at' => Carbon::parse('2026-06-08 17:00:00'),
        ]);

        $yamahaCost = CostHistory::query()
            ->where('invoice_entry_id', $entries['yamaha']->id)
            ->firstOrFail();

        PriceReviewTask::query()->create([
            'master_item_id' => $items['GENERATOR YAMAHA EF2600']->id,
            'invoice_entry_id' => $entries['yamaha']->id,
            'cost_history_id' => $yamahaCost->id,
            'previous_cost_amount' => 11050000,
            'current_selling_price' => 12950000,
            'status' => PriceReviewTask::STATUS_OPEN,
            'created_at' => Carbon::parse('2026-06-10 12:25:00'),
            'updated_at' => Carbon::parse('2026-06-10 12:25:00'),
        ]);
    }

    private function seedNotifications(array $users, Collection $items, array $entries): void
    {
        $notifications = [
            [
                'user' => $users['sri'],
                'type' => 'price_review_created',
                'title' => 'Review Harga',
                'body' => 'GENERATOR MATARI MPG4900 perlu review harga setelah kode modal berubah.',
                'url' => route('price-review-tasks.index'),
                'created_at' => Carbon::parse('2026-06-10 09:26:00'),
            ],
            [
                'user' => $users['sri'],
                'type' => 'price_review_created',
                'title' => 'Review Harga',
                'body' => 'GENERATOR YAMAHA EF2600 perlu review harga setelah stok baru masuk.',
                'url' => route('price-review-tasks.index'),
                'created_at' => Carbon::parse('2026-06-10 12:26:00'),
            ],
            [
                'user' => $users['sri'],
                'type' => 'incoming_checked',
                'title' => 'Cek Barang',
                'body' => $entries['lakoni']->invoice_number.' selesai dicek.',
                'url' => route('incoming-check-tasks.show', $entries['lakoni']->incomingCheckTask),
                'created_at' => Carbon::parse('2026-06-08 15:35:00'),
                'read_at' => Carbon::parse('2026-06-08 15:50:00'),
            ],
            [
                'user' => $users['wendy'],
                'type' => 'incoming_mismatch',
                'title' => 'Cek Barang',
                'body' => $entries['pump']->invoice_number.' ada selisih dan perlu dicek lagi.',
                'url' => route('incoming-check-tasks.show', $entries['pump']->incomingCheckTask),
                'created_at' => Carbon::parse('2026-06-09 16:12:00'),
            ],
            [
                'user' => $users['wendy'],
                'type' => 'incoming_checked',
                'title' => 'Cek Barang',
                'body' => $entries['lakoni']->invoice_number.' sudah dicek tanpa masalah.',
                'url' => route('incoming-check-tasks.show', $entries['lakoni']->incomingCheckTask),
                'created_at' => Carbon::parse('2026-06-08 15:36:00'),
                'read_at' => Carbon::parse('2026-06-08 16:00:00'),
            ],
            [
                'user' => $users['wendy'],
                'type' => 'price_review_created',
                'title' => 'Review Harga',
                'body' => 'GENERATOR YAMAHA EF2600 menunggu review harga.',
                'url' => route('price-review-tasks.index'),
                'created_at' => Carbon::parse('2026-06-10 12:27:00'),
            ],
            [
                'user' => $users['rina'],
                'type' => 'price_updated_up',
                'title' => 'Perubahan Harga',
                'body' => 'Harga jual GENERATOR MATARI MPG4900 naik pagi ini.',
                'url' => route('master-items.price-history', $items['GENERATOR MATARI MPG4900']),
                'created_at' => Carbon::parse('2026-06-10 09:40:00'),
            ],
            [
                'user' => $users['rina'],
                'type' => 'incoming_checked',
                'title' => 'Cek Barang',
                'body' => $entries['yamaha']->invoice_number.' masih menunggu cek gudang.',
                'url' => route('incoming-check-tasks.show', $entries['yamaha']->incomingCheckTask),
                'created_at' => Carbon::parse('2026-06-10 12:28:00'),
            ],
            [
                'user' => $users['william'],
                'type' => 'incoming_mismatch',
                'title' => 'Cek Barang',
                'body' => 'WATER PUMP HONDA WB20XT perlu hitung ulang karena ada satu unit kurang.',
                'url' => route('incoming-check-tasks.show', $entries['pump']->incomingCheckTask),
                'created_at' => Carbon::parse('2026-06-09 16:11:00'),
            ],
            [
                'user' => $users['william'],
                'type' => 'incoming_checked',
                'title' => 'Cek Barang',
                'body' => 'GENERATOR YAMAHA EF2600 masuk antrean cek berikutnya.',
                'url' => route('incoming-check-tasks.show', $entries['yamaha']->incomingCheckTask),
                'created_at' => Carbon::parse('2026-06-10 12:29:00'),
            ],
        ];

        foreach ($notifications as $notification) {
            AppNotification::query()->create([
                'user_id' => $notification['user']->id,
                'type' => $notification['type'],
                'title' => $notification['title'],
                'body' => $notification['body'],
                'url' => $notification['url'] ?? null,
                'related_type' => null,
                'related_id' => null,
                'read_at' => $notification['read_at'] ?? null,
                'created_at' => $notification['created_at'],
                'updated_at' => $notification['created_at'],
            ]);
        }
    }

    private function createMatchedEntry(
        array $users,
        MasterItem $item,
        string $invoiceNumber,
        string $vendor,
        Carbon $date,
        string $rawName,
        int $quantity,
        string $costCode,
        float $decodedCost,
        string $notes,
    ): InvoiceEntry {
        $entry = InvoiceEntry::query()->create([
            'invoice_number' => $invoiceNumber,
            'vendor' => $vendor,
            'invoice_date' => $date,
            'raw_item_name' => $rawName,
            'quantity' => $quantity,
            'expected_quantity' => $quantity,
            'cost_code' => $costCode,
            'decoded_cost_amount' => $decodedCost,
            'notes' => $notes,
            'master_item_id' => $item->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => $users['wendy']->id,
            'created_at' => $date->copy()->setTime(9, 15),
            'updated_at' => $date->copy()->setTime(9, 15),
        ]);

        CostHistory::query()->create([
            'master_item_id' => $item->id,
            'invoice_entry_id' => $entry->id,
            'vendor' => $vendor,
            'cost_code' => $costCode,
            'decoded_cost_amount' => $decodedCost,
            'recorded_by' => $users['wendy']->id,
            'recorded_at' => $date->copy()->setTime(9, 20),
            'created_at' => $date->copy()->setTime(9, 20),
            'updated_at' => $date->copy()->setTime(9, 20),
        ]);

        return $entry;
    }
}
