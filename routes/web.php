<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoviesController;
use App\Http\Controllers\movieCoverUpload;
use Inertia\Inertia;
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});



    


Route::get('/dashboard', [MoviesController::class, 'search'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/ai', [movieCoverUpload::class, 'ai_call']);



Route::get('/handlelist', [MoviesController::class, 'handlelist'])->name('handlelist');
Route::post('/updatehandlelistmovie', [MoviesController::class, 'updatehandlelistmovie'])->name('updatehandlelistmovie');
Route::post('/updatemovie', [MoviesController::class, 'updatemovie'])->name('updatemovie');



Route::post('/search/themoviedb', [MoviesController::class, 'searchTheMovieDb'])->name('searchthemovedb');


Route::get('/import', [MoviesController::class, 'readFromFile']);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
