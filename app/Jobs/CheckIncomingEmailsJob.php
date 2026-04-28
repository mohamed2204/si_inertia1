<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;

class CheckIncomingEmailsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info("CheckIncomingEmailsJob started");

        $client = app(ClientManager::class)->make([
            'host' => config('imap.accounts.default.host'),
            'port' => config('imap.accounts.default.port'),
            'encryption' => config('imap.accounts.default.encryption'),
            'username' => config('imap.accounts.default.username'),
            'password' => config('imap.accounts.default.password'),
            'protocol' => 'imap',
        ]);

        try {
            $client->connect();
        } catch (AuthFailedException|ConnectionFailedException|ImapBadRequestException|ImapServerErrorException|ResponseException|RuntimeException $e) {

        }

        $folder = $client->getFolder('INBOX');

        // emails non lus
       // $messages = $folder->query()->unseen()->get();
        $messages = $folder->query()->get();

        foreach ($messages as $message) {
            $to = $message->getTo();
            $to = $this->extractEmail($to);

            log::info(sprintf("mail to : %s", $to));

            $ValidTo = env('TARGET_EMAIL');
            // filtrer par adresse cible
            if (str_contains($to, $ValidTo)) {
                log::info('Email à traiter');
                // Dispatch traitement individuel
                $attachmentsPaths = [];

                foreach ($message->getAttachments() as $attachment) {
                    $filename = uniqid() . '.' . $attachment->getExtension();
                    $path = storage_path('app/temp/' . $filename);

                    $attachment->save(storage_path('app/temp'), $filename);

                    // save as pdf
                    log::info('save as pdf');
                    $this->excelToPdf($path);

                    $attachmentsPaths[] = $path;
                }

                ProcessEmailJob::dispatch([
                    'from' => $message->getFrom()[0]->mail ?? null,
                    'attachments' => $attachmentsPaths,
                ]);
            }else{
                log::info('Email non traitée');
            }
            // marquer comme lu
            $message->setFlag('Seen');
        }

        Log::info("CheckIncomingEmailsJob finished");
    }

    function extractEmail($string): ?string
    {
        if (preg_match('/<([^>]+)>/', $string, $matches)) {
            return $matches[1];
        }

        return filter_var($string, FILTER_VALIDATE_EMAIL) ? $string : null;
    }

    public function excelToPdf($file)
    {
        //$inputFileName = storage_path('app/test.xlsx');

        $inputFileName = $file;

        $spreadsheet = IOFactory::load($inputFileName);

        $writer = new Dompdf($spreadsheet);

        $outputFileName = storage_path('app/test.pdf');

        $writer->save($outputFileName);

        return response()->download($outputFileName);
    }
}
