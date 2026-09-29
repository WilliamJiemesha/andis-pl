<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_resolution_tickets', function (Blueprint $table) {
            $table->foreignId('resolved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_master_item_id')->nullable()->after('resolved_by')->constrained('master_items')->nullOnDelete();
            $table->string('resolution_type')->nullable()->after('resolved_master_item_id');
            $table->text('resolution_note')->nullable()->after('resolution_type');
            $table->timestamp('resolved_at')->nullable()->after('resolution_note');
        });
    }

    public function down(): void
    {
        Schema::table('item_resolution_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropConstrainedForeignId('resolved_master_item_id');
            $table->dropColumn(['resolution_type', 'resolution_note', 'resolved_at']);
        });
    }
};
