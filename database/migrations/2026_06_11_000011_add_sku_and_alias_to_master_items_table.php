<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('tipe');
            $table->string('alias_name')->nullable()->after('official_name');
        });

        DB::table('master_items')
            ->orderBy('id')
            ->get()
            ->each(function (object $item): void {
                $sku = 'SKU-'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT);

                DB::table('master_items')
                    ->where('id', $item->id)
                    ->update([
                        'sku' => $sku,
                        'alias_name' => $item->official_name,
                    ]);
            });

        Schema::table('master_items', function (Blueprint $table) {
            $table->unique('sku');
            $table->index('alias_name');
        });
    }

    public function down(): void
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropIndex(['alias_name']);
            $table->dropColumn(['sku', 'alias_name']);
        });
    }
};
