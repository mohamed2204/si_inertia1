<?php

namespace App\Filament\Resources\LabRequisResource\Pages;

use App\Filament\Resources\LabRequisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLabRequis extends EditRecord
{
    protected static string $resource = LabRequisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
