<?php

namespace App\Filament\Resources\LaboratoireResource\Pages;

use App\Filament\Resources\LaboratoireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaboratoire extends EditRecord
{
    protected static string $resource = LaboratoireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
