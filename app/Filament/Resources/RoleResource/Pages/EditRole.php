<?php

namespace App\Filament\Resources\RoleResource\Pages;

use AllowDynamicProperties;
use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;
    // Déclaration indispensable
    public array $permissionsToSync = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = $this->record->permissions->pluck('name');

//        $grouped = Permission::all()
//            ->groupBy(groupBy: fn($permission) => RoleResource::parsePermission($permission->name));

        $grouped = Permission::all()
            ->filter(function ($permission) {

                $name = $permission->name;

                // ignore relations pivot
                if (str($name)->contains(['_has_', 'attach_', 'detach_'])) {
                    return false;
                }

                [$action, $resource] = RoleResource::parsePermission($name);

                return in_array($action, RoleResource::allowedActions());
            })
            ->groupBy(function ($permission) {
                [, $resource] = RoleResource::parsePermission($permission->name);
                return $resource;
            });

        foreach ($grouped as $resource => $perms) {
            $data['permissions'][$resource] = $perms
                ->pluck('name')
                ->intersect($permissions)
                ->values()
                ->toArray();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $permissions = collect($data['permissions'] ?? [])
            ->flatten() // 🔥 important
            ->filter()
            ->values()
            ->toArray();

        //dd($permissions);

        $this->record->syncPermissions($permissions);

        unset($data['permissions']);

        return $data;
    }

//    protected function mutateFormDataBeforeSave(array $data): array
//    {
//        $permissions = collect($data['permissions'] ?? [])
//            ->flatMap(function ($actions, $resource) {
//                return collect($actions)
//                    ->filter()
//                    ->map(fn($value, $action) => "{$action}_{$resource}");
//            })
//            ->values()
//            ->toArray();
//
//        $this->record->syncPermissions($permissions);
//
//        unset($data['permissions']);
//
//        return $data;
//    }

    protected function CreateSmartRoles(): void
    {

        // 6) Rôles intelligents (auto assign)

        // 👉 Seeder recommandé :

        $manager = Role::firstOrCreate(['name' => 'manager']);

        $permissions = Permission::all()->filter(function ($p) {
            return !str($p->name)->startsWith('delete');
        });

        $manager->syncPermissions($permissions);
    }


    protected function hasStickyFooter(): bool
    {
        return true;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
