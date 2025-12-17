<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    

    protected $fillable = [
        'name',
        'tmdb_id',
        'imdb_id',
        'profile_path',
    ];

            protected $table = "actors";

    public function movies()
    {
        return $this->belongsToMany(Movie::class)
            ->withPivot(['character_name', 'cast_order'])
            ->withTimestamps();
    }
}
