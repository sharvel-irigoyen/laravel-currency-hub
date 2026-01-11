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
        Schema::table('precious_metal_prices', function (Blueprint $table) {
            // We assume table is empty or we don't care about conversion errors for now since it's dev/fresh
            // In strict scenarios, we would need raw statement or drop/add.
            // Since it's string, we can try change(), but it might fail if data exists and is not compatible format.
            // But we have empty db or junk data.
            $table->dateTime('market_time')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('precious_metal_prices', function (Blueprint $table) {
            $table->string('market_time')->change();
        });
    }
};
