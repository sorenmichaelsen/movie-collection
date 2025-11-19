<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoviesController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
    
Route::post('/movies/createFromCamera', [MoviesController::class, 'createFromCamera']);

Route::post('/movies/createFromCamera1', [MoviesController::class, 'createFromCamera1']);

Route::post('/movies/create', [MoviesController::class, 'create']);
Route::get('/movies/createFromCam', [MoviesController::class, 'createFromCam']);

Route::get('/fetchmovie', [MoviesController::class, 'fetchimdbid']);
Route::get('/search/themoviedb', [MoviesController::class, 'searchthemovedb'])->name('searchthemovedb');
Route::get('/search/themoviedbdetails', [MoviesController::class, 'theMovieDbDetails'])->name('themoviedbdetails');

