<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_check_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('expected_quantity');
            $table->unsignedInteger('checked_quantity')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique('invoice_entry_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_check_tasks');
    }
};
