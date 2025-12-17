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
Schema::create('actor_movie', function (Blueprint $table) {
$table->id();
$table->foreignId('actor_id')->constrained()->onDelete('cascade');
$table->foreignId('movie_id')->constrained()->onDelete('cascade');


// fields from TMDb credits
$table->string('character_name')->nullable();
$table->integer('cast_order')->nullable()->index();


$table->timestamps();


$table->unique(['actor_id', 'movie_id']);
});
}


public function down()
{
Schema::dropIfExists('actor_movie');
}
};
