<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoviesController;
use App\Http\Controllers\ManualMovieHandlingController;

use App\Http\Controllers\movieCoverUpload;
use App\Models\Movie;
use App\Services\TheMovieDbApiService;
use Inertia\Inertia;
use App\Jobs\imageDownloader;
Route::get('/', function () {
$movies = Movie::where("localimg",true)->whereNotNull('poster_path')->inRandomOrder()->limit(420)->get('poster_path');
  
return Inertia::render('Welcome', [
        'imgs'=> $movies,
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('Welcome');



Route::get('/dashboard', [MoviesController::class, 'search'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/ai', [movieCoverUpload::class, 'ai_call']);



Route::get('/handlelist', [ManualMovieHandlingController::class, 'handlelist'])->name('handlelist');
Route::post('/updatehandlelistmovie', [ManualMovieHandlingController::class, 'updatehandlelistmovie'])->name('updatehandlelistmovie');
Route::post('/updatemovie', [ManualMovieHandlingController::class, 'updatemovie'])->name('updatemovie');



Route::post('/search/themoviedb', [MoviesController::class, 'searchTheMovieDb'])->name('searchthemovedb');


Route::get('/import', [MoviesController::class, 'readFromFile']);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
