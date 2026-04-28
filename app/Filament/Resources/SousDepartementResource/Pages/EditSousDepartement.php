<?php

namespace App\Filament\Resources\SousDepartementResource\Pages;

use App\Filament\Resources\SousDepartementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSousDepartement extends EditRecord
{
    protected static string $resource = SousDepartementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
