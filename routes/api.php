<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\movieImporter;
use App\Http\Controllers\MoviesController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
    
Route::post('/movies/createFromCamera', [movieImporter::class, 'createFromCamera']);

Route::post('/movies/create', [movieImporter::class, 'create']);
Route::post('/movies/createFromCam', [movieImporter::class, 'createFromCam']);

Route::get('/fetchmovie', [MoviesController::class, 'fetchimdbid']);
Route::get('/search/themoviedb', [MoviesController::class, 'searchthemovedb'])->name('searchthemovedb');
Route::get('/search/themoviedbdetails', [MoviesController::class, 'theMovieDbDetails'])->name('themoviedbdetails');

Route::get('/movies/search', [MoviesController::class, 'localSearch']);
