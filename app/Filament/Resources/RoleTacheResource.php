<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleTacheResource\Pages;
use App\Models\RoleTache;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleTacheResource extends Resource
{
    protected static ?string $model = RoleTache::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 5;


    // Titre affiché dans le menu
    protected static ?string $modelLabel = 'Rôle / Jour';
    protected static ?string $pluralModelLabel = 'Dictionnaire des Rôles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuration du Rôle')
                    ->description('Définissez un libellé qui sera utilisé dans les tableaux de désignation.')
                    ->schema([
                        Forms\Components\TextInput::make('libelle')
                            ->label('Libellé du rôle ou du jour')
                            ->placeholder('ex: Responsable 1, Vendredi, Garde Nuit...')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('categorie')
                            ->label('Catégorie par défaut')
                            ->options([
                                'responsable' => 'Responsable',
                                'jour' => 'Jour de la semaine',
                                'remplacant' => 'Remplaçant',
                                'autre' => 'Autre',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('ordre')
                            ->label('Ordre de tri')
                            ->numeric()
                            ->default(0)
                            ->helperText('Utilisé pour classer les jours de Vendredi à Jeudi par exemple.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordre')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('libelle')
                    ->label('Nom du Rôle / Jour')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('categorie')
                    ->label('Catégorie')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'responsable' => 'danger',
                        'jour' => 'success',
                        'remplacant' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('ordre') // Important pour respecter votre logique Ven -> Jeu
            ->filters([
                Tables\Filters\SelectFilter::make('categorie')
                    ->options([
                        'responsable' => 'Responsables',
                        'jour' => 'Jours',
                        'remplacant' => 'Remplaçants',
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
            'index' => Pages\ListRoleTaches::route('/'),
            'create' => Pages\CreateRoleTache::route('/create'),
            'edit' => Pages\EditRoleTache::route('/{record}/edit'),
        ];
    }
}
