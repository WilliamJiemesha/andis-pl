<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_items', function (Blueprint $table) {
            $table->id();
            $table->string('barang');
            $table->string('merk');
            $table->string('tipe');
            $table->string('official_name')->unique();
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['barang', 'merk', 'tipe']);
            $table->index(['barang', 'merk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_items');
    }
};
