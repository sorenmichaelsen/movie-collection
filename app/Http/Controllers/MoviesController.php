<?php
namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class MoviesController extends Controller
{

    // public function readFromFile()
    // {

    //     $CSV     = Storage::disk('local')->get('list.csv');
    //     $lines   = explode(PHP_EOL, $CSV);
    //     $header  = collect(str_getcsv(array_shift($lines)));
    //     $rows    = collect($lines);
    //     $counter = 0;

    //     foreach ($rows as $row) {
    //         $data = explode(",", $row);
    //     $title = $data[1];
    //     $title = str_replace("Special edition", "", $title);
    //     $title = str_replace("(DVD)", "", $title);
    //     enrichMovieJobV2::dispatch($data[1], $data[2], $data[3] ?? "DVD", $data[4] ?? 00);

    //     }

    // }

    public function search(Request $request)
    {
        $search    = $request->input('search');
        $sort      = $request->input('sort', 'title');    // default sort by title
        $direction = $request->input('direction', 'asc'); // default asc

        $query = Movie::query();

        // --- Search filter ---
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('imdb_id', 'like', "%{$search}%")
                    ->orWhere('releast_at', 'like', "%{$search}%");
            });
        }

        // --- Allowed sortable columns ---
        $allowedSorts = ['title', 'releast_at', 'quantity'];

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'title'; // fallback
        }

        // --- Sorting ---
        $query->orderBy($sort, $direction);

        // --- Pagination ---
        $movies = $query->paginate(10)->withQueryString();

        return Inertia::render('Dashboard', [
            'movies'    => $movies,
            'search'    => $search,
            'sort'      => $sort,
            'direction' => $direction,
        ]);
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

    public function localSearch(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        // Split input i søgeord
        $terms = preg_split('/\s+/', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY);

        $query = Movie::query();

        // Hvert ord skal findes et sted i titlen (AND)
        foreach ($terms as $term) {
            $query->whereRaw("LOWER(title) LIKE ?", ["%{$term}%"]);
        }

        $movies = $query
            ->select('id', 'title', 'releast_at', 'poster_path')
            ->orderBy('title')
            ->limit(30)
            ->get();

        return response()->json($movies);
    }

}
