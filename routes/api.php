<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;
use App\Http\Controllers\BarcodeGenerator\BarcodeGeneratorController;
use App\Http\Controllers\UserAuthentication\UserAuthenticationController;


Route::post('/duplicates', [DuplicateCheckerController::class, 'store'])->middleware(['client', 'custom-security']);
Route::post('/lpass', [DuplicateCheckerController::class, 'lpass'])->middleware('client');
Route::get('/barcode', [BarcodeGeneratorController::class, 'create']);
Route::get('/accuracy', [DuplicateCheckerController::class, 'evaluateDuplicates']);
Route::post('/customer-auth', [UserAuthenticationController::class, 'authenticateUser'])->middleware('client');
