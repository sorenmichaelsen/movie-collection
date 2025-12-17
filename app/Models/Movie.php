<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'title',
        'alternative_title',
        'director',
        'actors',
        'quantity',
        'eannumber',
        'mediatype',
        'plot',
        'approved',
        'poster_path',
        'backdrop_path',
        'imdb_id',
        'danish_title',
        'duration',
        'releast_at',
        'rating',
        'hd',
        'plex_id',
        'tmdb_id'

    ];

    protected $casts = [
        'actors' => 'array',
    ];

    protected $table = "movies";
    
    public function actors()
    {
        return $this->belongsToMany(Actor::class)
            ->withPivot(['character_name', 'cast_order'])
            ->withTimestamps();
    }
    public function genres()
    {
        return $this->belongsToMany(Genre::class)
                    ->withTimestamps();
    }
}
