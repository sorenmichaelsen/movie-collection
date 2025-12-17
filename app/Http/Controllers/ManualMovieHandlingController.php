<?php
namespace App\Http\Controllers;

use App\Models\manualMovieHandling;
use App\Services\TheMovieDbApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManualMovieHandlingController extends Controller
{
    public function updatehandlelistmovie(Request $request)
    {
        $tmdb               = new TheMovieDbApiService;
        $remove_from_manual = manualMovieHandling::find($request->id);

        $details  = $tmdb->getMovieDetails($request->tmdb_id);
        $credits  = $tmdb->getMovieCredits($request->tmdb_id);
        $external = $tmdb->getExternalIds($request->tmdb_id);

        $store = $tmdb->importMovieWithCredits($request->tmdb_id, $remove_from_manual->eannumber ?? null, $remove_from_manual->mediatype ?? DVD, $remove_from_manual->title ?? "");

        $remove_from_manual->delete();
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
}
