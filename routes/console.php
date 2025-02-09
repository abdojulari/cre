<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Route::get('/duplicate-detector', function (Schedule $schedule) {
    $schedule->call(function () {
        Artisan::call('route:call', ['uri' => 'api/duplicate-detector']);
    })->twiceDaily(8, 23);
});
