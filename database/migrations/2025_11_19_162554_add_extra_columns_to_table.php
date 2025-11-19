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
        Schema::table('movies', function (Blueprint $table) {
            $table->string('danish_title')->nullable();
            $table->string('duration')->nullable();
            $table->string('released')->nullable();
            $table->float('rating')->nullable();
            $table->boolean('hd')->default(0);
            $table->integer('plex_id')->nullable();


            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('danish_title');
            $table->dropColumn('duration');
            $table->dropColumn('released');
            $table->dropColumn('rating');
            $table->dropColumn('hd');
            $table->dropColumn('plex_id');

        });
        
    }
};
