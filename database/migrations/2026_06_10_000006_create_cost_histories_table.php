<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor');
            $table->string('cost_code')->nullable();
            $table->decimal('decoded_cost_amount', 15, 2)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['master_item_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_histories');
    }
};
