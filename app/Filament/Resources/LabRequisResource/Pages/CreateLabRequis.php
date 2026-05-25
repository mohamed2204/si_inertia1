<?php

namespace App\Filament\Resources\LabRequisResource\Pages;

use App\Filament\Resources\LabRequisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLabRequis extends CreateRecord
{
    protected static string $resource = LabRequisResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
