<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('previous_price', 15, 2)->nullable();
            $table->decimal('new_price', 15, 2)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('effective_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
