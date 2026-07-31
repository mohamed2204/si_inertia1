<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingExcelEmails;
use App\Jobs\SendTestEmailJob;
use Illuminate\Console\Command;

class TestJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:job';
    protected $description = 'Déclenche manuellement le job de test';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        // SendTestEmailJob::dispatch(
        //     'admin@example.com',
        //     'Déclenche manuellement le job de test',
        //     'Ceci est un Déclenche manuellement le job de test.'
        // );
        ProcessIncomingExcelEmails::dispatch();
        $this->info('Job envoyé dans la file d\'attente avec succès !');
    }
}
