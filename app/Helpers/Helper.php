<?php

namespace App\Helpers;
class Helper
{
    static function sanitizeFilename(string $filename): string
    {
        // 1. Supprimer les accents
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);

        // 2. Remplacer tout ce qui n'est pas autorisé
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        // 3. Supprimer les underscores multiples
        $filename = preg_replace('/_+/', '_', $filename);

        // 4. Supprimer underscores au début et à la fin
        return trim($filename, '_');
    }
}


