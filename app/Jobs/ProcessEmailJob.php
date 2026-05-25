<?php

namespace App\Jobs;

use App\Imports\ExcelImport;
use App\Mail\DecisionMail;
use App\Services\ExcelDecisionService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;


class ProcessEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        log::info('ProcessEmailJob started');


        if (!isset($this->data['attachments']) || !is_array($this->data['attachments'])) {
            Log::error('attachments manquants ou invalides', $this->data);
            return;
        }

        if (empty($this->data['attachments'])) {
            Log::info('Aucun fichier à traiter');
            return;
        }

        Log::info('DATA JOB', $this->data);

        foreach ($this->data['attachments'] as $path) {

            Log::info('Traitement fichier : ' . $path);

            if (!file_exists($path)) {
                Log::error('Fichier introuvable : ' . $path);
                continue;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (!in_array($extension, ['xlsx', 'xls'])) {
                Log::info('Extension ignorée : ' . $extension);
                continue;
            }

            Log::info('Extension OK : ' . $extension);

            if (!in_array($extension, ['xlsx', 'xls'])) {
                continue;
            }

            log::info('Extension fichier OK');

            $dir = storage_path('app/temp');

            // Créer dossier si absent
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            Log::info('Dir exists: ' . (is_dir(storage_path('app/temp')) ? 'YES' : 'NO'));

            // Vérification
            if (!File::isDirectory($dir)) {
                throw new Exception('Le dossier temp n\'existe pas');
            }

//            $filename = uniqid() . '.' . $extension;
//
//            // Sauvegarde
//           $attachment->save($dir, $filename);
//
//            $path = $dir . '/' . $filename;

//            // Sauvegarder fichier
//            $path = storage_path('app/temp/' . $attachment->name);
//            $attachment->save(storage_path('app/temp'), $attachment->name);

            // Lire Excel
            $data = Excel::toArray(new ExcelImport, $path);

            // Analyse
            $service = new ExcelDecisionService();
            $decision = $service->analyze($data[0] ?? []);

            // Envoyer réponse


            Mail::to($this->data['from'])->send(new DecisionMail($decision));

            // Marquer comme lu
            //$this->message->setFlag('Seen');
        }
        log::info('ProcessEmailJob finished');

        //dd($this->data);
    }
}
