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
            $table->string("alternative_title")->nullable();
            $table->string("director")->nullable();
            $table->json("actors")->nullable();
            $table->integer("year")->nullable();
            $table->tinyInteger('amount')->default(1);
            $table->bigInteger("eannumber")->nullable();
            $table->string("mediatype")->nullable();
            $table->boolean("quantity")->default(0);
            $table->string("imgpath")->nullable();
            $table->longText('plot')->nullable();

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
