<?php
namespace App\Jobs;

use App\Services\TheMovieDbApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\manualMovieHandling;
use Log;

class enrichMovieJobV2 implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private $title;
    private $year;
    private $media;
    private $eannumber;

    public function __construct($title, $year, $media = null, $eannumber = null)
    {
        $this->title = $title;
        $this->year  = $year;
        $this->media = $media;
        $this->eannumber   = $eannumber;

    }

    /**
     * Execute the job.
     */
    public function handle(TheMovieDbApiService $tmdb): void
    {
        Log::info([$this->title, $this->year, $this->media]);
        
        if(strlen($this->year) != 4) {
            Log::info("NOT INT".$this->year);
            $this->year = 0;
        }
        $searchResponse = $tmdb->searchMovieByTitle($this->title, $this->year);

        if (! $searchResponse) {
            // Log::info("Manually fix");
        }

        // 'results' er en array. Tag første resultat (hvis nogen)
        $results = $searchResponse['results'] ?? [];

        if (count($results) === 0) {
            // Log::info("Manually fix");
            manualMovieHandling::create(["title" => $this->title, "eannumber" => $this->eannumber ?? null, "mediatype" => $this->media ?? null, "year" => $this->year]);

        }
        else if (count($results) == 1) {

            $first  = $results[0];
            $tmdbId = $first['id'];

            // Hent detaljer, credits og external ids
            $details  = $tmdb->getMovieDetails($tmdbId);
            $credits  = $tmdb->getMovieCredits($tmdbId);
            $external = $tmdb->getExternalIds($tmdbId);
            $store    = $tmdb->importMovieWithCredits($tmdbId, $this->eannumber ?? null, "DVD", $this->title);
            // Log::info("STORED CORRECTLY");
            //return response()->json(['created' => 1, 'message' => 'Success'], 202);

        } else {
            // Log::info("Manually fix");

            manualMovieHandling::create(["title" => $this->title, "eannumber" => $this->eannumber, "mediatype" => $this->media, "year" => $this->year]);
            //return response()->json(['created' => 0, 'message' => 'for mange resultater'], 202);

        }
    }
    }
