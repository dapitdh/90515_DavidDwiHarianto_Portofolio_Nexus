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
        Schema::create('users', function (Blueprint $table) {
            $table->string('id_user', 50)->primary();
            $table->string('nama_depan', 50);
            $table->string('nama_belakang', 50);
            $table->string('no_telepon', 15)->unique(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
