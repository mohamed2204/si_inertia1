<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;


class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
    // 1. Il faut déclarer la propriété ici !
    public array $permissionsToSync = [];

    protected function afterCreate(): void
    {
        $permissions = collect($this->data['permissions'] ?? [])
            ->flatten()
            ->unique()
            ->toArray();

        $this->record->syncPermissions($permissions);
    }


    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     // On extrait les permissions pour ne pas que Laravel tente de les insérer en SQL
    //     //$this->permissionsToSync = $data['permissions'] ?? [];
    //     $this->permissionsToSync = $this->form->getRawState()['permissions'] ?? [];
    //     unset($data['permissions']);

    //     return $data;
    // }

    // protected function afterCreate(): void
    // {
    //     /** @var Role $role */
    //     $role = $this->getRecord();
    //     // On utilise la méthode de Spatie pour synchroniser la table pivot
    //     //$this->record->syncPermissions($this->permissionsToSync);
    //     $role->syncPermissions($this->permissionsToSync);
    // }
}
