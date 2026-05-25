<?php

namespace App\Filament\Resources\LaboratoireResource\Pages;

use App\Filament\Resources\LaboratoireResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaboratoires extends ListRecords
{
    protected static string $resource = LaboratoireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
