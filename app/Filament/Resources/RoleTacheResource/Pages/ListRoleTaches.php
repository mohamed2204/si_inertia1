<?php

namespace App\Filament\Resources\RoleTacheResource\Pages;

use App\Filament\Resources\RoleTacheResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoleTaches extends ListRecords
{
    protected static string $resource = RoleTacheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
