<?php

use App\Http\Controllers\Api\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/albaHomes', [HomeController::class, 'albaHomesAllData'])->name('albaHomesAllData');
Route::post('/albaHomesWithParams', [HomeController::class, 'albaHomesDataWithParams'])->name('albaHomesWithParams');
