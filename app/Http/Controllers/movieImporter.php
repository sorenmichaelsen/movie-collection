<?php
namespace App\Http\Controllers;

use App\Jobs\enrichMovieJob;
use App\Jobs\enrichMovieJobV2;
use Illuminate\Http\Request;
use Log;

class movieImporter extends Controller
{
    public function createFromCamera(Request $request)
    {
##Obsolete
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
        try {
            Log::info("her");
            enrichMovieJobV2::dispatch($request->title, $request->year, $request->media ?? "DVD", $request->ean ?? null);

        } catch (\Throwable $e) {
            Log::error('Couldnt start job ' . $e->getMessage());

        }

    }

}
