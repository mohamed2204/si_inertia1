<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelController extends Controller
{
    public function genererRapport(): StreamedResponse
    {
        // 1. Chemin vers le fichier modèle
        $cheminModele = storage_path('app/modele_rapport.xlsx');

        if (!file_exists($cheminModele)) {
            abort(404, "Le fichier modèle n'existe pas.");
        }

        // 2. Charger le modèle Excel
        $spreadsheet = IOFactory::load($cheminModele);
        $feuille = $spreadsheet->getActiveSheet();

        // 3. Remplir les cellules de ton choix
        $feuille->setCellValue('B2', 'Mohamed Jenane'); // Exemple textuel
        $feuille->setCellValue('B3', date('d/m/Y'));    // Date du jour
        $feuille->setCellValue('C5', 1500);             // Donnée numérique

        // 4. Définir le nom final du fichier téléchargé
        $nomFichierFinal = 'rapport_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // 5. Créer un writer pour le format XLSX
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        // 6. Forcer le téléchargement via une réponse Streamed de Laravel
        // (Évite d'écrire un fichier physique sur le serveur, tout passe par la RAM)
        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $nomFichierFinal . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}