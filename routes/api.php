<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;


Route::get('/duplicates', [DuplicateCheckerController::class, 'index'])->middleware('client');
Route::get('/duplicates/{id}', [DuplicateCheckerController::class, 'show'])->middleware('client');
Route::put('/duplicates/{id}', [DuplicateCheckerController::class,'update'])->middleware('client');
Route::delete('/duplicates/{id}', [DuplicateCheckerController::class,'destroy'])->middleware('client');
Route::post('/duplicates', [DuplicateCheckerController::class, 'store'])->middleware('client');
