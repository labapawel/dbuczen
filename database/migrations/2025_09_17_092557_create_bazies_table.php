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
        Schema::create('bazies', function (Blueprint $table) {
            $table->id();
            // dodaj pola user_id, type:int, username:string, password:str, db:str, host:str, data_wygasniacia:date
            $table->unsignedBigInteger('user_id')->delete('cascade');
            $table->string('type');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('db');
            $table->string('host');
            $table->boolean('niewygasa')->default(false);
            $table->date('data_wygasniacia')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bazies');
    }
};
