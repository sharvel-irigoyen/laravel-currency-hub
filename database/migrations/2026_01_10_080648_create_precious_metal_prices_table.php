<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('precious_metal_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metal_id')->constrained('precious_metals')->onDelete('cascade');
            // $table->string('currency')->default('USD'); // Assuming USD always for now or moving to relation
            // $table->string('unit')->default('OZ'); // Source is always OZ
            $table->decimal('bid', 10, 2);
            $table->decimal('ask', 10, 2);
            $table->decimal('change_val', 8, 2);
            $table->decimal('change_percent', 5, 2);
            $table->decimal('low', 10, 2);
            $table->decimal('high', 10, 2);
            $table->datetime('market_time')->index(); // Changed to datetime and indexed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precious_metal_prices');
    }
};
