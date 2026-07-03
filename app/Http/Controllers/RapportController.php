<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Importation de la façade DomPDF

class RapportController extends Controller
{
    public function telechargerRapport()
    {
        // 1. Préparation des données (provenant de votre BDD ou statiques)
        $info1 = "placeholder 1";
        $info2 = "placeholder 2";

        $items = [
            ['date' => 'd1', 'col1' => '', 'col2' => ''],
            ['date' => 'd1', 'col1' => '', 'col2' => ''],
            ['date' => 'd3', 'col1' => '', 'col2' => ''],
            ['date' => 'd4', 'col1' => '', 'col2' => ''],
            ['date' => 'd5', 'col1' => '', 'col2' => ''],
            ['date' => 'd6', 'col1' => '', 'col2' => ''],
            ['date' => 'd7', 'col1' => '', 'col2' => ''],
        ];

        // 2. Chargement de la vue avec les données passées en tableau
        $pdf = Pdf::loadView('pdf.rapport', compact('info1', 'info2', 'items'));

        // 3. Optionnel : Forcer le format A4 et l'orientation portrait (au cas où)
        $pdf->setPaper('a4', 'portrait');

        // 4. Téléchargement direct du fichier PDF
        //return $pdf->download('rapport-' . date('Y-m-d') . '.pdf');

        // ASTUCE : Si vous préférez d'abord l'afficher à l'écran dans le navigateur :
        return $pdf->stream('rapport.pdf');
    }
}