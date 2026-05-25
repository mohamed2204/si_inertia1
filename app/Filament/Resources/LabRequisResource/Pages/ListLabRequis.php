<?php

namespace App\Filament\Resources\LabRequisResource\Pages;

use App\Filament\Resources\LabRequisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLabRequis extends ListRecords
{
    protected static string $resource = LabRequisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
