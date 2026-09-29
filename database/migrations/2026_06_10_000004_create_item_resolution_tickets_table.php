<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_resolution_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_entry_id')->constrained()->cascadeOnDelete();
            $table->string('raw_item_name');
            $table->string('vendor');
            $table->string('status')->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('invoice_entry_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_resolution_tickets');
    }
};
