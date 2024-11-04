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
        Schema::create('kategori_detail', function (Blueprint $table) {
            $table->string('id_kategoridetail', 50)->primary();
            
            $table->string('id_kategoriutama', 50);
            $table->foreign('id_kategoriutama')
                  ->references('id_kategoriutama')
                  ->on('kategori_utama')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->string('nama_detail', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_detail');
    }
};
