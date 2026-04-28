<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasActionPermissions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Checkbox;
use Forms\Components\View;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administration';
    protected static bool $shouldRegisterNavigation = true;

    // use HasActionPermissions;
    public static function getPagePrefix(): string
    {
        return 'role';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                    ]),

                Section::make('Permissions')
                    ->schema([
                        Tabs::make('Permissions')
                            ->tabs(fn () => self::getPermissionTabs())
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function getPermissionTabs(): array
    {
        $permissions = Permission::all()
            ->filter(fn ($permission) => !self::isRelationPermission($permission->name))
            ->groupBy(fn ($permission) => self::getResourceFromPermission($permission->name));

        //dd($permissions);

        return $permissions->map(function ($perms, $resource) {

            return Tab::make(ucfirst($resource))
                ->schema([
                    CheckboxList::make("permissions.$resource")
                        ->label('')
                        ->options(
                            $perms->mapWithKeys(fn ($perm) => [
                                $perm->name => self::formatPermissionLabel($perm->name),
                            ])->toArray()
                        )
                        ->columns(2)
                        ->bulkToggleable(),
                ]);

        })->values()->toArray();
    }


//    protected static function getPermissionTabs(): array
//    {
//        $permissions = Permission::all()
//            ->groupBy(fn($p) => self::getResourceFromPermission($p->name));
//
//        return $permissions->map(function ($perms, $resource) {
//
//            $actions = $perms->map(fn($p) => self::getActionFromPermission($p->name))
//                ->unique();
//
//            return Tab::make(ucfirst($resource))
//                ->schema([
//                    Grid::make(count($actions) + 1)
//                        ->schema([
//                            Forms\Components\View::make('filament.empty')
//                                ->viewData([]),
//
//                            ...$actions->map(
//                                fn($action) =>
//                                Forms\Components\View::make('filament.action-header')
//                                    ->viewData(['action' => self::formatActionLabel($action)])
//                            ),
//
//                            ...[
//                                Forms\Components\View::make('filament.resource-header')
//                                    ->viewData(['resource' => ucfirst($resource)]),
//
//                                ...$actions->map(function ($action) use ($resource) {
//
//                                    $permissionName = "{$action}_{$resource}";
//
//                                    return Checkbox::make("permissions.{$resource}.{$action}")
//                                        ->label('')
//                                        ->default(false)
//                                        ->dehydrated(false)
//                                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($resource, $action) {
//
//                                            // règle métier
//                                            if ($action === 'view' && $state) {
//                                                $set("permissions.$resource.view_any", true);
//                                            }
//                                        })
//                                        ->formatStateUsing(function ($state, $record) use ($permissionName) {
//                                            return $record?->hasPermissionTo($permissionName);
//                                        });
////                                        ->formatStateUsing(function () use ($permissionName) {
////                                            return request()->record?->hasPermissionTo($permissionName);
////                                        });
//                                }),
//                            ]
//                        ])
//                ]);
//
//        })->values()->toArray();
//    }

    public static function allowedActions(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'validate',
        ];
    }
    protected static function isRelationPermission(string $permission): bool
    {
        return str($permission)->contains([
            '_has_',
            'attach_',
            'detach_',
            '::'
        ]);
    }
    protected static function getResourceFromPermission($permission)
    {
        return last(explode('_', $permission));
    }

    protected static function getActionFromPermission($permission)
    {
        $parts = explode('_', $permission);
        array_pop($parts);
        return implode('_', $parts);
    }

    public static function parsePermission($permission)
    {
        $parts = explode('_', $permission);

        $resource = array_pop($parts);
        $action = implode('_', $parts);

        return [$action, $resource];
    }

    protected static function formatPermissionLabel(string $name): string
    {
        return str($name)
            ->replace('_', ' ')
            ->replace('view any', 'Voir liste')
            ->replace('view', 'Voir')
            ->replace('create', 'Créer')
            ->replace('update', 'Modifier')
            ->replace('delete', 'Supprimer')
            ->title();
    }


    protected static function formatActionLabel($action): string
    {
        return match ($action) {
            'view_any' => 'Liste',
            'view' => 'Voir',
            'create' => 'Créer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'validate' => 'Valider',
            default => ucfirst($action),
        };
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('permissions.name')
                    ->label('Permissions')
                    ->limit(50)
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }


}
