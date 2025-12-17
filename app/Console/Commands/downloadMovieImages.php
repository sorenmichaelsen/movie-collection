<?php
namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class downloadMovieImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-movie-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $movies = Movie::whereNotNull("backdrop_path")->get();
        foreach ($movies as $m) {
                $url      = "https://image.tmdb.org/t/p/w300" . $m->backdrop_path;
                $contents = @file_get_contents($url);

                if ($contents != false) {

                    Storage::disk('public')->put("backdrop/w300" . $m->poster_path, $contents);

                }
        }
        $movies = Movie::whereNotNull("poster_path")->get();
        foreach ($movies as $m) {
                $url      = "https://image.tmdb.org/t/p/w300" . $m->backdrop_path;
                $contents = @file_get_contents($url);

                if ($contents != false) {

                    Storage::disk('public')->put("poster/w300" . $m->poster_path, $contents);

                }
        }
    }
}
