<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SousDepartementResource\Pages;
use App\Filament\Resources\SousDepartementResource\RelationManagers;
use App\Models\SousDepartement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SousDepartementResource extends Resource
{
    protected static ?string $model = SousDepartement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 2; // En 2 position

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('departement_id')
                    ->relationship('departement', 'nom')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('nom')
                    ->label('Nom du Laboratoire')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Toggle::make('est_actif')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

// Dans la table (table)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('departement.nom')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nom')
                    ->label('Laboratoire')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('est_actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('departement')
                    ->relationship('departement', 'nom')
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSousDepartements::route('/'),
            'create' => Pages\CreateSousDepartement::route('/create'),
            'edit' => Pages\EditSousDepartement::route('/{record}/edit'),
        ];
    }
}
