<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport; // Votre classe d'importation Excel

class ProcessIncomingExcelEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Liste des expéditeurs autorisés
    protected array $allowedSenders = [
        'fournisseur@domaine.com',
        'partenaire@entreprise.com',
    ];

    public function handle(): void
    {
        // Connexion au compte IMAP par défaut
        $client = Client::account('default');
        $client->connect();

        // Récupérer la boîte de réception (INBOX)
        $folder = $client->getFolder('INBOX');

        // Récupérer les messages non lus
        $messages = $folder->query()->unseen()->get();

        foreach ($messages as $message) {
            $senderEmail = strtolower($message->getFrom()[0]->mail);

            // 1. Vérifier si l'expéditeur fait partie de la liste autorisée
            if (!in_array($senderEmail, array_map('strtolower', $this->allowedSenders))) {
                continue;
            }

            // 2. Vérifier et parcourir les pièces jointes
            if ($message->hasAttachments()) {
                foreach ($message->getAttachments() as $attachment) {
                    $extension = strtolower($attachment->getExtension());
                    
                    // Vérifier si c'est un fichier Excel (.xlsx ou .xls)
                    if (in_array($extension, ['xlsx', 'xls'])) {
                        
                        // Sauvegarder la pièce jointe temporairement
                        $filePath = 'imports/' . uniqid() . '_' . $attachment->getName();
                        Storage::disk('local')->put($filePath, $attachment->getContent());

                        try {
                            // 3. Traitement du fichier Excel avec Laravel Excel
                            Excel::import(new DataImport, Storage::path($filePath));
                            
                            Log::info("Fichier Excel traité avec succès : {$attachment->getName()} depuis {$senderEmail}");
                        } catch (\Exception $e) {
                            Log::error("Erreur lors du traitement de l'Excel : " . $e->getMessage());
                        } finally {
                            // Nettoyage du fichier temporaire
                            Storage::disk('local')->delete($filePath);
                        }
                    }
                }
            }

            // Marquer le message comme lu pour éviter de le retraiter
            $message->setFlag('Seen');
        }
    }
}
