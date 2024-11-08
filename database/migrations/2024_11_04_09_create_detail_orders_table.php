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
        Schema::create('detail_orders', function (Blueprint $table) {
            $table->string('id_detailorder', 50)->primary();

            $table->string('id_order', 50);
            $table->foreign('id_order')
                  ->references('id_order')
                  ->on('orders')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->string('id_menu', 50);
            $table->foreign('id_menu')
                  ->references('id_menu')
                  ->on('menus')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->integer('kuantitas');
            $table->integer('harga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_orders');
    }
};
