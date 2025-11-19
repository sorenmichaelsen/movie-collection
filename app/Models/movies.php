<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class movies extends Model
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
        'imgpath',
        'imdb_id',
        'danish_title',
        'duration',
        'released',
        'ration',
        'hd',
        'plex_id',


    ];

     protected $casts = [
        'actors' => 'array',
    ];

        protected $table = "movies";
}


    