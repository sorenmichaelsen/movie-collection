<?php
namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class imageDownloader implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private $imagetype;
    private $image;
    private $movie;
    public function __construct($imagetype, $image, Movie $movie)
    {
        $this->imagetype = $imagetype;
        $this->image     = $image;
        $this->movie     = $movie;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->image != null) {

            $url      = "https://image.tmdb.org/t/p/original" . $this->image;
            $contents = @file_get_contents($url);

            $url300      = "https://image.tmdb.org/t/p/w300" . $this->image;
            $contents300 = @file_get_contents($url);
            if ($contents != false) {

                if ($this->imagetype == "backdrop") {

                    Storage::disk('public')->put("backdrop" . $this->image, $contents);
                    Storage::disk('public')->put("backdrop/w300" . $this->image, $contents300);

                }
                if ($this->imagetype == "poster") {

                    Storage::disk('public')->put("poster/" . $this->image, $contents);
                    Storage::disk('public')->put("poster/w300" . $this->image, $contents300);

                }
                $movie           = $this->movie; // allerede objekt
                $movie->localimg = 1;
                $movie->save();
            }
        }
    }
}
