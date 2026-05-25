<?php

namespace App\Jobs;

use App\Imports\ExcelImport;
use App\Services\ExcelDecisionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Mail;

use Illuminate\Foundation\Bus\Dispatchable;
use Webklex\IMAP\Facades\Client;
use Maatwebsite\Excel\Facades\Excel;
//use App\Imports\ExcelImport;
//use App\Services\ExcelDecisionService;
use App\Mail\DecisionMail;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;

class ProcessIncomingEmails implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * @throws RuntimeException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws GetMessagesFailedException
     * @throws ConnectionFailedException
     * @throws AuthFailedException
     * @throws MessageFlagException
     * @throws ResponseException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     */
    public function handle(): void
    {
        $client = Client::account('default');
        $client->connect();

        $folder = $client->getFolder('INBOX');

        $messages = $folder->query()
            ->unseen()
            ->get();

        foreach ($messages as $message) {

            // Vérifier destinataire
            $to = collect($message->getTo())->pluck('mail')->toArray();

            $to = collect($message->getTo() ?? [])
                ->map(fn($r) => $r->mail ?? $r->address ?? null)
                ->filter()
                ->toArray();

            if (!in_array(env('TARGET_EMAIL'), $to)) {
                continue;
            }

            // Vérifier pièces jointes
            foreach ($message->getAttachments() as $attachment) {

                $extension = strtolower($attachment->getExtension());

                if (!in_array($extension, ['xlsx', 'xls'])) {
                    continue;
                }

                // Sauvegarder fichier
                $path = storage_path('app/temp/' . $attachment->name);
                $attachment->save(storage_path('app/temp'), $attachment->name);

                // Lire Excel
                $data = Excel::toArray(new ExcelImport, $path);

                // Analyse
                $service = new ExcelDecisionService();
                $decision = $service->analyze($data[0] ?? []);

                // Envoyer réponse
                $from = $message->getFrom()[0]->mail;

                Mail::to($from)->send(new DecisionMail($decision));

                // Marquer comme lu
                $message->setFlag('Seen');
            }
        }
    }
}

