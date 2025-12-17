<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
Schema::create('actors', function (Blueprint $table) {
$table->id();
$table->string('name');
$table->unsignedBigInteger('tmdb_id')->nullable()->index();
$table->string('imdb_id')->nullable()->index();
$table->string('profile_path')->nullable(); // TMDB image path
$table->timestamps();


$table->unique(['tmdb_id']);
});
}


public function down()
{
Schema::dropIfExists('actors');
}
};
