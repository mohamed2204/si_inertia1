<?php


namespace App\Filament\Resources;

use App\Filament\Resources\LabRequisResource\Pages;
use App\Models\LabRequis;
use App\Models\Laboratoire;
use App\Models\RoleTache;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class LabRequisResource extends Resource
{
    protected static ?string $model = LabRequis::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Définition du Requis')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('laboratoire_id')
                            ->label('Laboratoire')
                            ->options(Laboratoire::all()->pluck('nom', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('role_tache_id')
                            ->label('Rôle ou Jour')
                            ->options(RoleTache::all()->pluck('libelle', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('section')
                            ->options([
                                'responsables' => 'Section Responsables',
                                'jours' => 'Section Planning (Ven-Jeu)',
                                'remplacants' => 'Section Remplaçants',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('nombre_requis')
                            ->label('Nombre de membres requis')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('ordre')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('laboratoire.nom')
                    ->label('Laboratoire')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('roleTache.libelle')
                    ->label('Rôle / Jour')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'responsables' => 'danger',
                        'jours' => 'success',
                        'remplacants' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('nombre_requis')
                    ->label('Quota')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('laboratoire_id')
                    ->label('Filtrer par Labo')
                    ->relationship('laboratoire', 'nom'),

                SelectFilter::make('section')
                    ->options([
                        'responsables' => 'Responsables',
                        'jours' => 'Planning',
                        'remplacants' => 'Remplaçants',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabRequis::route('/'),
            'create' => Pages\CreateLabRequis::route('/create'),
            'edit' => Pages\EditLabRequis::route('/{record}/edit'),
        ];
    }
}
