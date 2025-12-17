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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable()->index();
            $table->timestamps();
            $table->string("title");
            $table->string("alternative_title")->nullable();
            $table->string("director")->nullable();
            $table->tinyInteger('quantity')->default(0);
            $table->string("eannumber")->nullable();
            $table->string("mediatype")->nullable();
            $table->string("poster_path")->nullable();
            $table->string("backdrop_path")->nullable();
            $table->longText('plot')->nullable();
            $table->integer('duration')->nullable();    // minutter
            $table->date('releast_at')->nullable(); 
            $table->decimal('rating', 3, 1)->nullable();
            $table->boolean('hd')->default(0);
            $table->boolean('localimg')->default(0);
            $table->string("imdb_id")->nullable();
            $table->string("plex_id")->nullable();
            $table->boolean("ripped")->default(0);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
