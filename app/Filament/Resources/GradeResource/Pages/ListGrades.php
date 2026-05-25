<?php

namespace App\Filament\Resources\GradeResource\Pages;

use App\Filament\Resources\GradeResource;
use App\Filament\Pages\ImportData;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrades extends ListRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Votre nouveau bouton Importer
            Actions\Action::make('import')
                ->label('Importer des élèves')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                // Redirige vers la page personnalisée que nous avons créée précédemment
                ->url(fn(): string => ImportData::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
