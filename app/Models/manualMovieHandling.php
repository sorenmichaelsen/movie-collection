<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class manualMovieHandling extends Model
{
         protected $fillable = [
        'title',
        'alternative_title',
        'director',
        'actors',
        'year',
        'quantity',
        'eannumber',
        'mediatype',
        'plot',
        'approved',
        'imgpath'

    ];

     protected $casts = [
        'actors' => 'array',
    ];

        protected $table = "manual_movie_handlings";
}
