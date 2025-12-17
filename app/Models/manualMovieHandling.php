<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class manualMovieHandling extends Model
{
         protected $fillable = [
        'title',
        'year',
        'eannumber',
        'mediatype'
    ];


        protected $table = "manual_movie_handlings";
}
