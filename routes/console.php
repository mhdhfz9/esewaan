<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('contracts:send-expiry-notification')->daily();

Schedule::command('contracts:send-under-three-months-reminder')
    ->weeklyOn(1, '09:00')
    ->timezone('Asia/Kuala_Lumpur');
