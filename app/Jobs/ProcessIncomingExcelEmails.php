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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;

class ProcessIncomingExcelEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Liste des expéditeurs autorisés
    protected array $allowedSenders = [
        'fournisseur@domaine.com',
        'partenaire@entreprise.com',
        'admin@exemple.com'
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

        Log::info("Nombre de messages non lus : " . count($messages));

        // SendTestEmailJob::dispatch(
        //     'admin@exemple.com',
        //     'Traitement des e-mails Excel',
        //     'Ceci est un traitement automatique des e-mails entrants contenant des fichiers Excel.<br>Nombre de messages non lus : ' . count($messages)
        // );

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

                    SendTestEmailJob::dispatch(
                        'admin@exemple.com',
                        'Vérifier si c\'est un fichier Excel',
                        'Fichier attaché : ' . $attachment->getName()
                    );

                    // Vérifier si c'est un fichier Excel (.xlsx ou .xls)
                    if (in_array($extension, ['xlsx', 'xls'])) {

                        //$content = $attachment->getContent();
                        // 1. Assurer que le dossier temporaire existe dans storage/app/imports
                        // 1. S'assurer que le dossier 'imports' existe sur le disque local (créera storage/app/private/imports)
                        Storage::disk('local')->makeDirectory('imports');

                        // 2. Récupérer le chemin ABSOLU du dossier grâce à la configuration du Disk 'local'
                        $targetDirectory = Storage::disk('local')->path('imports');

                        // 3. Préparer le nom unique du fichier
                        $fileName = uniqid() . '_' . $attachment->getName();

                        // 4. Sauvegarder la pièce jointe
                        $saved = $attachment->save($targetDirectory, $fileName);

                        if (!$saved) {
                            Log::error("Impossible d'enregistrer la pièce jointe : {$attachment->getName()}");
                            continue;
                        }

                        // 5. Récupérer les chemins pour l'import et le nettoyage
                        $relativePath = 'imports/' . $fileName;
                        $fullPath = Storage::disk('local')->path($relativePath);

                        try {
                            // 6. Importer le fichier Excel
                            Excel::import(new DataImport, $fullPath);

                            // TO PDF
                            // Définir la langue en français pour les dates PHP
                            setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr', 'fra', 'french');

                            // Si vous utilisez IntlDateFormatter ou Carbon dans Laravel :
                            \Carbon\Carbon::setLocale('fr');
                            // 1. Charger le fichier Excel existant
                            $spreadsheet = IOFactory::load($fullPath);

                            // 2. Configurer le rendu PDF
                            $writer = new Dompdf($spreadsheet);

                            // Désactiver l'affichage du quadrillage (Gridlines) dans la feuille Excel
                            $spreadsheet->getActiveSheet()->setShowGridLines(false);
                            // Optionnel : Forcer l'orientation paysage si le tableau est large
                            $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);


                            // 3. Sauvegarder le PDF généré
                            $pdfRelativePath = 'imports/' . uniqid() . '.pdf';
                            $pdfFullPath = Storage::disk('local')->path($pdfRelativePath);

                            $writer->save($pdfFullPath);


                            Log::info("Fichier Excel traité avec succès : {$attachment->getName()} depuis {$senderEmail}");
                        } catch (\Exception $e) {
                            Log::error("Erreur lors du traitement de l'Excel : " . $e->getMessage());
                        } finally {
                            // 7. Nettoyer le fichier temporaire
                            //Storage::disk('local')->delete($relativePath);
                        }
                    }
                }
            }

            // Marquer le message comme lu pour éviter de le retraiter
            // TODO
            $message->setFlag('Seen');
        }
    }
}
