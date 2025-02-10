<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;
use App\Http\Controllers\BarcodeGenerator\BarcodeGeneratorController;
use App\Http\Controllers\UserAuthentication\UserAuthenticationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyAlertController;
use App\Notifications\DuplicateDetectionNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

Route::post('/duplicates', [DuplicateCheckerController::class, 'store'])->middleware(['client','throttle:duplicates', 'sanctum-auth']);
Route::post('/lpass', [DuplicateCheckerController::class, 'lpass'])->middleware('client');
Route::get('/barcode', [BarcodeGeneratorController::class, 'create'])->middleware('client');
Route::get('/accuracy', [DuplicateCheckerController::class, 'evaluateDuplicates']);
Route::post('/customer-auth', [UserAuthenticationController::class, 'authenticateUser'])->middleware('client');
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::get('/duplicate-detector', [DailyAlertController::class, 'duplicateDetectionAlert']);
Route::post('/barcode-lookup', [DailyAlertController::class, 'barcodeLookup']);
Route::get('/list-barcodes', [DailyAlertController::class, 'listBarcodes']);
Route::get('/test-slack-notification', function () {
    $duplicates = [
        ['firstname' => 'John', 'lastname' => 'Doe', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'email' => 'john.doe@example.com'],
        // Add more test data as needed
    ];

    Notification::route('slack', config('services.slack.notifications.webhook_url'))
        ->notify(new DuplicateDetectionNotification($duplicates));
    Log::info('Slack notification sent', $duplicates);
    return response()->json(['message' => 'Slack notification sent!']);
});
