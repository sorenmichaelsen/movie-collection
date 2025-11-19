<?php
namespace App\Console\Commands;

use App\Models\movies;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class plexParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:plex-parser';

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
        $plex = Storage::disk('local')->get('jamesbond.json');
        if (json_validate($plex)) {

            $data   = json_decode($plex, true);
            $movies = $data['MediaContainer']['Metadata'];
            $count  = 0;
            foreach ($movies as $plexmovie) {
                $hd   = 0;
                $disk = "DVD";
                if ($plexmovie['Media'][0]['videoResolution'] > 576) {
                    $hd   = 1;
                    $disk = "Bluray";
                }
                $title         = $plexmovie['title'];
                $originalTitle = $title;
                if (isset($plexmovie['originalTitle'])) {
                    $originalTitle = $plexmovie['originalTitle'];
                }
                $movieData = [
                    "originalTitle" => $originalTitle,
                    "danishTitle"   => $title,
                    "duration"      => $plexmovie['duration'],
                    "released"      => $plexmovie['originallyAvailableAt'],
                    "rating"        => $plexmovie['rating'] ?? null,
                    "hd"            => $hd,
                    'plex_id'       => $plexmovie['ratingKey'],
                ];

                $myMovie = movies::where("title", $originalTitle)->where("year", $plexmovie['year'])->first();

                if ($myMovie == null) {
                    movies::create([
                        'title'             => $originalTitle,
                        'alternative_title' => $originalTitle,
                        'year'              => $plexmovie['year'],
                        'quantity'          => 1,
                        'mediatype'         => "PLEX",
                        'danish_title' => $title,
                        'duration'          => $movieData['duration'],
                        'released'          => $movieData['released'],
                        'rating'            => $movieData['rating'],
                        'hd'                => $movieData['hd'],
                        'plex_id'           => $movieData['plex_id'],
                    ]);
             
                } else {
                    $myMovie->danish_title = $title;
                    $myMovie->duration     = $movieData['duration'];
                    $myMovie->released     = $movieData['released'];
                    $myMovie->rating       = $movieData['rating'];
                    $myMovie->hd           = $movieData['hd'];
                    $myMovie->plex_id      = $movieData['plex_id'];
                    if($myMovie->mediatype != "PLEX") {
                         $myMovie->mediatype    = $disk;
                    }
                    $myMovie->save();

              
                }
                $count = $count +1;

            }
            dd($count);

        }

    }
}
