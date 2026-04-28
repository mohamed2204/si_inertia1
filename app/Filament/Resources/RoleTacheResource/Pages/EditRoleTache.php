<?php

namespace App\Filament\Resources\RoleTacheResource\Pages;

use App\Filament\Resources\RoleTacheResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoleTache extends EditRecord
{
    protected static string $resource = RoleTacheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
