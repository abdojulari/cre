<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;
use App\Http\Controllers\BarcodeGenerator\BarcodeGeneratorController;


Route::get('/duplicates', [DuplicateCheckerController::class, 'index'])->middleware('client');
Route::get('/duplicates/{id}', [DuplicateCheckerController::class, 'show'])->middleware('client');
Route::put('/duplicates/{id}', [DuplicateCheckerController::class,'update'])->middleware('client');
Route::delete('/duplicates/{id}', [DuplicateCheckerController::class,'destroy'])->middleware('client');
Route::post('/duplicates', [DuplicateCheckerController::class, 'store'])->middleware('client');
Route::post('/lpass', [DuplicateCheckerController::class, 'lpass'])->middleware('client');
//Route::middleware('auth:api')->post('/duplicates', [DuplicateCheckerController::class, 'store']);
Route::get('/barcode', [BarcodeGeneratorController::class, 'create']);
