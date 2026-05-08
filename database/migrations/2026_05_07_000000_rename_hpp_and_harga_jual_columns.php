<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tb_pembelian_item: hpp -> cost_price, harga_jual -> selling_price
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            $table->renameColumn('hpp', 'cost_price');
            $table->renameColumn('harga_jual', 'selling_price');
        });

        // tb_pembelian: harga_jual -> total_amount, add total_selling_price
        Schema::table('tb_pembelian', function (Blueprint $table) {
            $table->renameColumn('harga_jual', 'total_amount');
            $table->decimal('total_selling_price', 15, 2)->default(0)->after('total_amount');
        });

        // tb_penjualan_item: hpp -> cost_price, harga_jual -> selling_price
        Schema::table('tb_penjualan_item', function (Blueprint $table) {
            $table->renameColumn('hpp', 'cost_price');
            $table->renameColumn('harga_jual', 'selling_price');
        });

        // laporan_laba_rugis: total_hpp -> total_cost
        Schema::table('laporan_laba_rugis', function (Blueprint $table) {
            $table->renameColumn('total_hpp', 'total_cost');
        });
    }

    public function down(): void
    {
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            $table->renameColumn('cost_price', 'hpp');
            $table->renameColumn('selling_price', 'harga_jual');
        });

        Schema::table('tb_pembelian', function (Blueprint $table) {
            $table->dropColumn('total_selling_price');
            $table->renameColumn('total_amount', 'harga_jual');
        });

        Schema::table('tb_penjualan_item', function (Blueprint $table) {
            $table->renameColumn('cost_price', 'hpp');
            $table->renameColumn('selling_price', 'harga_jual');
        });

        Schema::table('laporan_laba_rugis', function (Blueprint $table) {
            $table->renameColumn('total_cost', 'total_hpp');
        });
    }
};