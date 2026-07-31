<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Webklex\IMAP\Facades\Client;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

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

        foreach ($messages as $message) {
            $senderEmail = strtolower($message->getFrom()[0]->mail);

            // 1. Vérifier si l'expéditeur est autorisé
            if (!in_array($senderEmail, array_map('strtolower', $this->allowedSenders))) {
                continue;
            }

            // 2. Traiter les pièces jointes
            if ($message->hasAttachments()) {
                foreach ($message->getAttachments() as $attachment) {
                    $extension = strtolower($attachment->getExtension());

                    // Vérifier l'extension (.xlsx ou .xls)
                    if (in_array($extension, ['xlsx', 'xls'])) {
                        $tempFileName = uniqid('temp_') . '_' . $attachment->getName();
                        $tempRelativePath = 'imports/temp/' . $tempFileName;

                        try {
                            // S'assurer que le dossier temporaire existe
                            Storage::disk('local')->makeDirectory('imports/temp');
                            $tempDirectory = Storage::disk('local')->path('imports/temp');

                            // Sauvegarder la pièce jointe temporairement
                            $saved = $attachment->save($tempDirectory, $tempFileName);

                            if (!$saved) {
                                Log::error("Impossible d'enregistrer la pièce jointe temporaire : {$attachment->getName()}");
                                continue;
                            }

                            $fullPath = Storage::disk('local')->path($tempRelativePath);

                            // ----------------------------------------------------
                            // A. LECTURE EXCEL & VÉRIFICATION DE LA DATE DE DÉBUT
                            // ----------------------------------------------------
                            $spreadsheet = IOFactory::load($fullPath);

                            // Activer la langue française pour les formules Excel
                            Calculation::getInstance($spreadsheet)->setLocale('fr');

                            $sheet = $spreadsheet->getActiveSheet();

                            // Récupérer la cellule de date (Exemple: cellule B1 ou B2)
                            $rawDateValue = $sheet->getCell('B1')->getCalculatedValue();
                            $startDate = $this->parseExcelDate($rawDateValue);

                            if (!$startDate) {
                                throw new \Exception("Impossible d'extraire une date valide depuis la cellule B1.");
                            }

                            // Définir la date limite d'expiration (ex: 48 heures de validité à partir de la date Excel)
                            $expirationDate = $startDate->copy()->addDays(5);

                            // Vérifier si la procédure a expiré
                            if (now()->greaterThan($expirationDate)) {
                                $this->logToDatabase($senderEmail, $attachment->getName(), '', $startDate, $expirationDate, 'expired', 'Procédure expirée');
                                Log::warning("Procédure expirée pour l'e-mail de {$senderEmail}. Date de début: {$startDate}, Expiration: {$expirationDate}");
                                continue;
                            }


                            SendTestEmailJob::dispatch(
                                'admin@exemple.com',
                                'Vérifier les références du fichier Excel',
                                'Informations : ' . $rawDateValue . ' | ' . $startDate . ' | ' . $expirationDate
                                . '<br>Chemin complet : ' . $fullPath
                            );

                            // ----------------------------------------------------
                            // B. IMPORTATION DES DONNÉES EN BASE
                            // ----------------------------------------------------
                            // Excel::import(new DataImport, $fullPath);

                            // ----------------------------------------------------
                            // C. CONVERSION EN PDF
                            // ----------------------------------------------------
                            $spreadsheet->getActiveSheet()->setShowGridLines(false);
                            $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

                            $writer = new Dompdf($spreadsheet);
                            $pdfFileName = pathinfo($attachment->getName(), PATHINFO_FILENAME) . '_' . time() . '.pdf';

                            // Dossier de l'expéditeur nettoyé
                            $cleanSenderFolder = $this->sanitizeFolderName($senderEmail);
                            $pdfRelativePath = "imports/{$cleanSenderFolder}/{$pdfFileName}";

                            // Créer le dossier expéditeur s'il n'existe pas
                            Storage::disk('local')->makeDirectory("imports/{$cleanSenderFolder}");
                            $pdfFullPath = Storage::disk('local')->path($pdfRelativePath);

                            // Enregistrer le PDF
                            $writer->save($pdfFullPath);

                            // ----------------------------------------------------
                            // D. DÉPLACER L'EXCEL DANS LE DOSSIER EXPÉDITEUR
                            // ----------------------------------------------------
                            $finalExcelPath = "imports/{$cleanSenderFolder}/" . uniqid() . '_' . $attachment->getName();
                            Storage::disk('local')->move($tempRelativePath, $finalExcelPath);

                            // ----------------------------------------------------
                            // E. JOURNALISATION DANS LA BASE DE DONNÉES
                            // ----------------------------------------------------
                            $this->logToDatabase($senderEmail, $attachment->getName(), $finalExcelPath, $startDate, $expirationDate, 'processed');

                            Log::info("Fichier Excel et PDF traités avec succès pour {$senderEmail}");

                        } catch (\Exception $e) {
                            $this->logToDatabase($senderEmail, $attachment->getName(), '', now(), now(), 'failed', $e->getMessage());
                            Log::error("Erreur lors du traitement de l'Excel ({$attachment->getName()}) : " . $e->getMessage());
                        } finally {
                            // Nettoyage du fichier temporaire s'il existe encore
                            if (Storage::disk('local')->exists($tempRelativePath)) {
                                Storage::disk('local')->delete($tempRelativePath);
                            }
                        }
                    }
                }
            }

            // Marquer le message comme lu pour éviter de le retraiter au prochain passage
            $message->setFlag('Seen');
        }
    }

    /**
     * Convertit une valeur de cellule Excel (Série numérique ou Texte) en objet Carbon
     */
    private function parseExcelDate(mixed $cellValue): ?Carbon
    {
        if (empty($cellValue)) {
            return null;
        }

        if (is_numeric($cellValue)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($cellValue));
        }

        try {
            $cleanValue = str_replace('-', '/', trim($cellValue));
            return Carbon::createFromFormat('d/m/Y', $cleanValue);
        } catch (\Exception $e) {
            return Carbon::parse($cellValue);
        }
    }

    /**
     * Nettoie le nom du dossier pour éviter les caractères interdits dans le système de fichier
     */
    private function sanitizeFolderName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-@\.]/', '_', $name);
    }

    /**
     * Insère un enregistrement dans la table de journalisation `imported_files`
     */
    private function logToDatabase(string $sender, string $filename, string $storedPath, Carbon $startDate, Carbon $expirationDate, string $status, ?string $error = null): void
    {
        DB::table('imported_files')->insert([
            'sender_email' => $sender,
            'original_filename' => $filename,
            'stored_path' => $storedPath,
            'excel_start_date' => $startDate->toDateTimeString(),
            'expiration_date' => $expirationDate->toDateTimeString(),
            'status' => $status,
            'error_message' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

// namespace App\Jobs;

// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log;
// use Webklex\IMAP\Facades\Client;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Imports\DataImport; // Votre classe d'importation Excel

// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
// use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
// use Carbon\Carbon;

// use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
// use PhpOffice\PhpSpreadsheet\Calculation\LocaleGenerator;

// class ProcessIncomingExcelEmails implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     // Liste des expéditeurs autorisés
//     protected array $allowedSenders = [
//         'fournisseur@domaine.com',
//         'partenaire@entreprise.com',
//         'admin@exemple.com'
//     ];

//     public function handle(): void
//     {
//         // Connexion au compte IMAP par défaut
//         $client = Client::account('default');
//         $client->connect();

//         // Récupérer la boîte de réception (INBOX)
//         $folder = $client->getFolder('INBOX');

//         // Récupérer les messages non lus
//         $messages = $folder->query()->unseen()->get();

//         Log::info("Nombre de messages non lus : " . count($messages));

//         // SendTestEmailJob::dispatch(
//         //     'admin@exemple.com',
//         //     'Traitement des e-mails Excel',
//         //     'Ceci est un traitement automatique des e-mails entrants contenant des fichiers Excel.<br>Nombre de messages non lus : ' . count($messages)
//         // );

//         foreach ($messages as $message) {
//             $senderEmail = strtolower($message->getFrom()[0]->mail);

//             // 1. Vérifier si l'expéditeur fait partie de la liste autorisée
//             if (!in_array($senderEmail, array_map('strtolower', $this->allowedSenders))) {
//                 continue;
//             }

//             // 2. Vérifier et parcourir les pièces jointes
//             if ($message->hasAttachments()) {
//                 foreach ($message->getAttachments() as $attachment) {
//                     $extension = strtolower($attachment->getExtension());

//                     // SendTestEmailJob::dispatch(
//                     //     'admin@exemple.com',
//                     //     'Vérifier si c\'est un fichier Excel',
//                     //     'Fichier attaché : ' . $attachment->getName()
//                     // );

//                     // Vérifier si c'est un fichier Excel (.xlsx ou .xls)
//                     if (in_array($extension, ['xlsx', 'xls'])) {
//                         try {



//                             //$content = $attachment->getContent();
//                             // 1. Assurer que le dossier temporaire existe dans storage/app/imports
//                             // 1. S'assurer que le dossier 'imports' existe sur le disque local (créera storage/app/private/imports)
//                             Storage::disk('local')->makeDirectory('imports');

//                             // 2. Récupérer le chemin ABSOLU du dossier grâce à la configuration du Disk 'local'
//                             $targetDirectory = Storage::disk('local')->path('imports');

//                             // 3. Préparer le nom unique du fichier
//                             $fileName = uniqid() . '_' . $attachment->getName();

//                             // 4. Sauvegarder la pièce jointe
//                             $saved = $attachment->save($targetDirectory, $fileName);

//                             if (!$saved) {
//                                 Log::error("Impossible d'enregistrer la pièce jointe : {$attachment->getName()}");
//                                 continue;
//                             }

//                             // 5. Récupérer les chemins pour l'import et le nettoyage
//                             $relativePath = 'imports/' . $fileName;
//                             $fullPath = Storage::disk('local')->path($relativePath);
//                             // 6. Importer le fichier Excel
//                             // Excel::import(new DataImport, $fullPath);


//                             // 1. Charger le fichier Excel
//                             $spreadsheet = IOFactory::load($fullPath);
//                             // Activer la langue française pour le moteur de calcul de PhpSpreadsheet
//                             Calculation::getInstance($spreadsheet)->setLocale('fr');
//                             // 2. Sélectionner la feuille active (ou par nom : $spreadsheet->getSheetByName('Feuil1'))
//                             $sheet = $spreadsheet->getActiveSheet();

//                             // --- OPTION A : Récupérer la valeur brute / saisie ---
//                             $rawValue = $sheet->getCell('B1')->getValue();  // la date du 28/07/2026 sera récupérée comme "44999" (numéro de série Excel)

//                             // --- OPTION B : Récupérer la valeur formatée (telle qu'affichée dans Excel) ---
//                             // Utile pour les dates (ex: "28/07/2026") ou les monnaies (ex: "15.00 €")
//                             $formattedValue = $sheet->getCell('B1')->getFormattedValue();

//                             // --- OPTION C : Récupérer le résultat d'une formule ---
//                             // Si A1 contient =SOMME(B1:B5), ceci renvoie le résultat calculé

//                             $calculatedValue = $sheet->getCell('B1')->getCalculatedValue();

//                             SendTestEmailJob::dispatch(
//                                 'admin@exemple.com',
//                                 'Vérifier les références du fichier Excel',
//                                 'Fichier attaché : ' . $rawValue . ' | ' . $formattedValue . ' | ' . $calculatedValue
//                                 . '<br>Chemin complet : ' . $fullPath
//                             );

//                             // TO PDF
//                             // Définir la langue en français pour les dates PHP
//                             //setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr', 'fra', 'french');

//                             // Si vous utilisez IntlDateFormatter ou Carbon dans Laravel :
//                             //Carbon::setLocale('fr');
//                             // 1. Charger le fichier Excel existant
//                             //$spreadsheet = IOFactory::load($fullPath);

//                             // 2. Configurer le rendu PDF
//                             $writer = new Dompdf($spreadsheet);

//                             // Désactiver l'affichage du quadrillage (Gridlines) dans la feuille Excel
//                             $spreadsheet->getActiveSheet()->setShowGridLines(false);
//                             // Optionnel : Forcer l'orientation paysage si le tableau est large
//                             $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);


//                             // 3. Sauvegarder le PDF généré
//                             $pdfRelativePath = 'imports/' . uniqid() . '.pdf';
//                             $pdfFullPath = Storage::disk('local')->path($pdfRelativePath);

//                             $writer->save($pdfFullPath);


//                             Log::info("Fichier Excel traité avec succès : {$attachment->getName()} depuis {$senderEmail}");
//                         } catch (\Exception $e) {
//                             Log::error("Erreur lors du traitement de l'Excel : " . $e->getMessage());
//                         } finally {
//                             // 7. Nettoyer le fichier temporaire
//                             //Storage::disk('local')->delete($relativePath);
//                         }
//                     }
//                 }
//             }

//             // Marquer le message comme lu pour éviter de le retraiter
//             // TODO
//             $message->setFlag('Seen');
//         }
//     }
// }
