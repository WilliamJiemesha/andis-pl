<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained()->cascadeOnDelete();
            $table->string('alias_name');
            $table->string('normalized_alias');
            $table->timestamps();

            $table->unique(['master_item_id', 'normalized_alias']);
            $table->index('normalized_alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_aliases');
    }
};
