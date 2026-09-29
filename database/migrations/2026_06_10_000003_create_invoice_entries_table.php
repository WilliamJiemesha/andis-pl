<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_entries', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->string('vendor');
            $table->date('invoice_date');
            $table->string('raw_item_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('expected_quantity')->nullable();
            $table->string('cost_code')->nullable();
            $table->decimal('decoded_cost_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('master_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_number', 'vendor']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_entries');
    }
};
