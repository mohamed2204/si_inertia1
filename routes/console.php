<?php

use App\Jobs\CheckIncomingEmailsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\ProcessIncomingExcelEmails;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



//Schedule::command('emails:process')
//    ->everyFiveMinutes()
//    ->withoutOverlapping();


// Schedule::job(new CheckIncomingEmailsJob)
//     ->everyTwoMinutes()
//     ->before(fn() => Log::info('⏰ Scheduler déclenché'))
//     ->after(fn() => Log::info('✅ Scheduler terminé'));



// // Exécute le Job toutes les minutes sans chevauchement
// Schedule::job(new ProcessIncomingExcelEmails)
//     ->everyMinute()
//     ->withoutOverlapping();

//use Illuminate\Support\Facades\Schedule;

// Exemple 1 : Exécuter une fermeture (closure) toutes les minutes
Schedule::call(function () {
    // Votre code ici
})->everyMinute();

// Exemple 2 : Exécuter une commande Artisan toutes les minutes
Schedule::command('test:job')->everyMinute();