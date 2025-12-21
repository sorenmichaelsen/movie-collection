<?php
namespace App\Http\Controllers;

use App\Models\manualMovieHandling;
use App\Models\Movie;
use App\Services\TheMovieDbApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;
class ManualMovieHandlingController extends Controller
{
    public function updatehandlelistmovie(Request $request)
    {
    //    Log::info($request->all());
        $tmdb               = new TheMovieDbApiService; 
        $remove_from_manual = manualMovieHandling::find($request->id);

        $details  = $tmdb->getMovieDetails($request->tmdb_id);
        $credits  = $tmdb->getMovieCredits($request->tmdb_id);
        $external = $tmdb->getExternalIds($request->tmdb_id);

        $store = $tmdb->importMovieWithCredits($request->tmdb_id, $remove_from_manual->eannumber ?? null, $request->selectedMedia['name'] ?? DVD, $remove_from_manual->title ?? "", $request->ripped ?? false, $request->movie_edition['name'] ?? "Standard", $request->storagebox ?? null, $request->scanimg ?? null);

        $remove_from_manual->delete();
    }
    public function deletehandlelistmovie(Request $request) {
        $movie = manualMovieHandling::find($request->id);
        $movie->delete();
        //return ["error"=>0,"msg"=>"Removed from manual handling list"];
    }
    public function updatemovie(Request $request)
    {
    }
    public function handlelist()
    {
        $movies = manualMovieHandling::orderBy('id', 'asc')->paginate(10); // important: paginate()

        return Inertia::render('Handlelist', [
            'movies' => $movies,
            'count'  => $movies->total(), // or your old $totalMovies if you prefer
        ]);
    }

    public function movieExist(Request $request) {
        $movieCount = Movie::where("tmdb_id",$request->tmdb_id)->count();
        return $movieCount;
    }

}
