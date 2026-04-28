<?php

use App\Jobs\CheckIncomingEmailsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



//Schedule::command('emails:process')
//    ->everyFiveMinutes()
//    ->withoutOverlapping();


Schedule::job(new CheckIncomingEmailsJob)
    ->everyTwoMinutes()
    ->before(fn () => Log::info('⏰ Scheduler déclenché'))
    ->after(fn () => Log::info('✅ Scheduler terminé'));
