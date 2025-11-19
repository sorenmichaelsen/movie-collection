<?php
namespace App\Http\Controllers;

use App\Jobs\enrichMovieJob;
use App\Jobs\StoreFromCamera;
use App\Models\manualMovieHandling;
use App\Models\movies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Log;

class MoviesController extends Controller
{

    public function handlelist()
    {
        $movies = manualMovieHandling::orderBy('id', 'asc')->paginate(10); // important: paginate()

        return Inertia::render('Handlelist', [
            'movies' => $movies,
            'count'  => $movies->total(), // or your old $totalMovies if you prefer
        ]);
    }

    public function create(Request $request)
    {
        enrichMovieJob::dispatch($request->title, $request->year, "DVD");

        $exist = movies::where("title", $request->title)->where("year", $request->year)->first();

        if ($exist->count() == 0) {
            movies::create($request->all());
        } else {
            $exist->quantity = $exist->quantity + 1;
            $exist->save();
        }

    }

    public function createFromCam(Request $request)
    {
        enrichMovieJob::dispatch($request->title, $request->year, $request->media ?? "DVD", $request->ean);
    }

    public function readFromFile()
    {

        $CSV     = Storage::disk('local')->get('list.csv');
        $lines   = explode(PHP_EOL, $CSV);
        $header  = collect(str_getcsv(array_shift($lines)));
        $rows    = collect($lines);
        $counter = 0;
        foreach ($rows as $row) {
            $data = explode(",", $row);
            enrichMovieJob::dispatch($data[1], $data[2], $data[3] ?? "DVD", $data[4] ?? 00, $row, $counter);
            $counter = $counter + 1;
        }

    }

    public function fetchimdbid(Request $request)
    {
        Log::info($request->imdbid);

        $response = Http::get('https://www.omdbapi.com/', [
            'i'      => $request->imdbid,
            'apikey' => 'd1bcc068',
        ]);
        return $response;
    }

    public function updatehandlelistmovie(Request $request)
    {Log::info($request->all());

        movies::create([
            'title'             => $request->title,
            'alternative_title' => $request->alternativetitle,
            'director'          => $request->director,
            'actors'            => $request->actors,
            'year'              => $request->year,
            'quantity'          => 1,
            'eannumber'         => $request->ean,
            'mediatype'         => $request->media ?? "DVD",
            'plot'              => $request->plot,
            'imgpath'           => $request->imgpath,
        ]);

        $manual = manualMovieHandling::find($request->id);
        $manual->delete();}

    public function updatemovie(Request $request)
    {
        Log::info($request->all());
        // $movie = movies::updateOrCreate(
        //     ['id' => $request->id],
        //     [$request->all()]
        // );

        $movie                    = movies::find($request->id);
        $movie->title             = $request->title;
        $movie->alternative_title = $request->alternativetitle;
        $movie->director          = $request->director;
        $movie->actors            = $request->actors;
        $movie->year              = $request->year;
        $movie->eannumber         = $request->ean;
        $movie->mediatype         = $request->media ?? "DVD";
        $movie->plot              = $request->plot;
        $movie->imgpath           = $request->imgpath;
        $movie->imdb_id           = $request->imdb_id;
        $movie->save();
        Log::info($movie);
    }

    public function search(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = $request->input('search');

        $moviesQuery = movies::query();

        if ($search) {
            // adjust columns as needed (title, description, etc.)
            $moviesQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('plot', 'like', "%{$search}%");
            });
        }

        $movies = $moviesQuery->orderBy('id', 'asc')->paginate(20)
            ->appends($request->only('search')); // preserve search on paginator links

        return Inertia::render('Dashboard', [
            'movies' => $movies,
            'search' => $search, // pass back initial search so client starts in-sync
        ]);
    }

    public function createFromCamera(Request $request)
    {

        $request->validate([
            'image' => 'required|file|image|max:1024', // optional validation
        ]);

        // Store file
        $filename = Str::random(10) . ".jpg";
        $year     = $request->year;
        if ($year == "None") {
            $year = null;
        }
        $path = $request->file('image')->storeAs('images', $filename, 'public');
        Log::info($request);
        StoreFromCamera::dispatch($request->title, $year, $request->director, $request->actors, $filename, "DVD");
    }
    public function createFromCamera1(Request $request)
    {

        $filename = Str::random(10) . ".jpg";

        StoreFromCamera::dispatch("Det Forsømte Forår", null, "", "", $filename, "DVD");
    }

    public function searchthemovedb(Request $request)
    {
        $title = str_replace("Special edition", "", $request->title);
        $title = str_replace("(DVD)", "", $title);
        if ($request->year) {
            $response = Http::withToken(ENV('TheMovieDb_token'))->get("https://api.themoviedb.org/3/search/movie?query=" . $title . "&year" . $request->year);

            if (isset($response->json()['total_results'])) {
                if ($response->json()['total_results'] == 0) {
                    $response = Http::withToken(ENV('TheMovieDb_token'))->get("https://api.themoviedb.org/3/search/movie?query=" . $title);

                }
            }
        } else {
            $response = Http::withToken(ENV('TheMovieDb_token'))->get("https://api.themoviedb.org/3/search/movie?query=" . $title);
        }

        return $response;

    }

    public function theMovieDbDetails(Request $request)
    {

        $response = Http::withToken(ENV('TheMovieDb_token'))->get("https://api.themoviedb.org/3/movie/" . $request->id);

        return $response;

    }

}
