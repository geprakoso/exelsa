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
        Schema::table('md_produk', function (Blueprint $table) {
            $table->enum('tipe_produk', ['physical', 'service'])->default('physical')->after('nama_produk');
            $table->boolean('is_sellable')->default(true)->after('tipe_produk');
            $table->boolean('is_purchasable')->default(true)->after('is_sellable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_produk', function (Blueprint $table) {
            $table->dropColumn(['tipe_produk', 'is_sellable', 'is_purchasable']);
        });
    }
};
