<?php

namespace App\Filament\Resources\DepartementResource\Pages;

use App\Filament\Resources\DepartementResource;
use App\Filament\Resources\DepartementResource\RelationManagers\SousDepartementsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartement extends EditRecord
{
    protected static string $resource = DepartementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            // Vous pouvez ajouter ici un widget qui compte le nombre de labos
        ];
    }

    public static function getRelations(): array
    {
        return [
            SousDepartementsRelationManager::class,
        ];
    }
}
