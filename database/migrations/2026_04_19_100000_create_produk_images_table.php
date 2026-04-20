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
        Schema::create('produk_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('md_produk')->onDelete('cascade');
            $table->string('original_name');           // Nama file asli user
            $table->string('disk')->default('public'); // Disk: public/r2/s3
            $table->string('path');                    // Path lengkap file webp
            $table->integer('size');                   // Size dalam bytes setelah kompresi
            $table->boolean('is_primary')->default(false); // Gambar utama?
            $table->integer('sort_order')->default(0); // Urutan tampilan
            $table->timestamps();
            
            // Index untuk performance
            $table->index('produk_id');
            $table->index(['produk_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_images');
    }
};
