<?php
namespace App\Jobs;

use App\Models\manualMovieHandling;
use App\Models\movies;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Log;
use App\Jobs\enrichMovieJobV2;

class StoreFromCamera implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private $title;
    private $year;
    private $director;
    private $actors;
    private $image;
    private $media;
    public function __construct($title, $year, $director, $actor, $image, $media)
    {
        $this->title   = $title;
        $this->year    = $year;
        $this->director   = $director;
        $this->actors     = $director;
        $this->image    = $image;
        $this->media = $media;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       // Log::info([$this->title, $this->year, $this->media]);

            try {
                if ($this->title) {
                    manualMovieHandling::create([
                        'title'     => $this->title,
                        'media'     => $this->media,
                        'year'      => $this->year,
                        'imgpath'   => $this->image,
                        'director'  => $this->director,
                        'actors'    => $this->actors,

                    ]);
                }
            } catch (Exception $e) {
                Log::info("---------------");
                Log::info("ERROR");
                Log::info($e);
            }

       
    }
}
