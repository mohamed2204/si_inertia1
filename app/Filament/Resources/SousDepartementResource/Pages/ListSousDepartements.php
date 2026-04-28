<?php

namespace App\Filament\Resources\SousDepartementResource\Pages;

use App\Filament\Resources\SousDepartementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSousDepartements extends ListRecords
{
    protected static string $resource = SousDepartementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
