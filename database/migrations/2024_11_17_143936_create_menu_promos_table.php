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
        Schema::create('menu_promos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_promo');
            $table->foreign('id_promo')->references('id_promo')->on('promos')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('id_menu');
            $table->foreign('id_menu')->references('id_menu')->on('menus')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_promos');
    }
};
