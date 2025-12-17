<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class manualMovieHandling extends Model
{
         protected $fillable = [
        'title',
        'year',
        'eannumber',
        'mediatype',
        'scanimg'
    ];


        protected $table = "manual_movie_handlings";
}
