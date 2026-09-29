<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_review_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_history_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('previous_cost_amount', 15, 2)->nullable();
            $table->decimal('current_selling_price', 15, 2)->nullable();
            $table->string('status')->default('open');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_review_tasks');
    }
};
