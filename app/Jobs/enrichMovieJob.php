<?php
namespace App\Jobs;

use App\Models\manualMovieHandling;
use App\Models\movies;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Log;

class enrichMovieJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private $title;
    private $year;
    private $media;
    private $ean;
    private $row;
    private $counter;
    public function __construct($title, $year, $media, $ean, $row, $counter)
    {
        $this->title   = $title;
        $this->year    = $year;
        $this->media   = $media;
        $this->ean     = $ean;
        $this->row     = $row;
        $this->counter = $counter;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //Log::info([$this->title, $this->year, $this->media]);

        $response = Http::get('https://www.omdbapi.com/', [
            't'      => $this->title,
            'type'   => 'movie',
            'y'      => $this->year ?? "",
            'apikey' => 'd1bcc068',
        ]);
        $data = $response->json();
        if ($data['Response'] == "False") {
            Log::info("--Issue with ".$this->title." counter ".$this->counter);
            Log::info($response->json());
            Log::info("----------------");
            try {
                if ($this->title) {
                    manualMovieHandling::create([
                        'title'     => $this->title,
                        'eannumber' => $this->ean,
                        'media'     => $this->media,
                        'year'      => $this->year,
                    ]);
                }
            } catch (Exception $e) {
                Log::info("---------------");
                Log::info("ERROR");
                Log::info("line number " . $this->counter);
                Log::info($raw);
            }

        } else {

            $movie = [
                'title'     => $data['Title'],
                'year'      => $data['Year'],
                'director'  => $data['Director'],
                'actors'    => json_encode($data['Actors']),
                'plot'      => $data['Plot'],
                'mediatype' => $this->media,
                'eannumber' => $this->ean,
                'quantity'  => 1,
            ];

            $exist = movies::where("title", $movie['title'])->limit(1)->get();

            if ($exist->count() == 0) {
                movies::create($movie);
                echo "created";
            } else {
                $exist->first()->quantity = $exist->first()->quantity + 1;
                $exist->first()->save();
                echo "count updated";
            }

        }

    }
}
