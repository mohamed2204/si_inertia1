<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class DataImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // $row contient les colonnes de chaque ligne Excel
            // Exemple : $row[0] pour la colonne A, $row[1] pour la colonne B...
            log::info('Row : ' . $row[0]);
        }
    }
}