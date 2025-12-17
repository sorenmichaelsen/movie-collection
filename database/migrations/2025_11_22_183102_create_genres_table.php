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
Schema::create('genres', function (Blueprint $table) {
$table->id();
$table->unsignedBigInteger('tmdb_id')->nullable()->index();
$table->string('name');
$table->timestamps();


$table->unique(['tmdb_id']);
});
}


public function down()
{
Schema::dropIfExists('genres');
}
};
