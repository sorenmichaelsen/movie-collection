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
        Schema::create('manual_movie_handlings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("title")->nullable();
            $table->string("eannumber")->nullable();
            $table->integer("year")->nullable();
            $table->string("mediatype")->nullable();
            $table->string("scanimg")->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_movie_handlings');
    }
};
