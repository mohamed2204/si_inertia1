<?php

namespace App\Enums;

enum SectionType: string
{
    case RESPONSABLE = 'responsable';
    case JOUR = 'jour';
    case REMPLACANT = 'remplacant';

    // Pour l'affichage dans les formulaires Filament
    public function getLabel(): string
    {
        return match($this) {
            self::RESPONSABLE => 'Responsables / Permanents',
            self::JOUR => 'Affectations Journalières (J+N)',
            self::REMPLACANT => 'Remplaçants',
        };
    }
}
