<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembreResource\Pages;
use App\Filament\Resources\MembreResource\RelationManagers;
use App\Models\Membre;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembreResource extends Resource
{
    protected static ?string $model = Membre::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $navigationGroup = 'Gestion des Labs';
    protected static ?int $navigationSort = 2;


    // app/Filament/Resources/MembreResource.php

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identité')
                ->schema([
                    TextInput::make('nom')
                        ->label('Nom')
                        ->required(),
                        //->placeholder('ex: Ahmed BENNANI'),
                    TextInput::make('prenom')
                        ->label('Prénom')
                        ->required(),
                        //->placeholder('ex: Ahmed BENNANI'),

                    TextInput::make('matricule')
                        ->label('Matricule / ID')
                        ->unique(ignoreRecord: true),
                ])->columns(2),

            Section::make('Affectation')
                ->schema([
                    Select::make('departement_id')
                        ->relationship('departement', 'nom')
                        ->label('Département')
                        ->required()
                        ->preload()
                        ->searchable(),

                    Toggle::make('est_actif')
                        ->label('Actif pour les désignations')
                        ->default(true)
                        ->helperText('Si décoché, ce membre n’apparaîtra plus dans les listes de choix des nouveaux plannings.'),
                ])->columns(2),

            Section::make('Contact')
                ->collapsed() // Optionnel, pour gagner de la place
                ->schema([
                    TextInput::make('email')
                        ->email(),
//                    TextInput::make('telephone')
//                        ->label('Téléphone')
//                        ->tel(),
                ]),
        ]);
    }

    // app/Filament/Resources/MembreResource.php

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('departement.nom')
                    ->label('Département')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->copyable(),

                IconColumn::make('est_actif')
                    ->label('Statut')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('departement')
                    ->relationship('departement', 'nom'),

                Tables\Filters\TernaryFilter::make('est_actif')
                    ->label('Filtrer par statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListMembres::route('/'),
            'create' => Pages\CreateMembre::route('/create'),
            'edit' => Pages\EditMembre::route('/{record}/edit'),
        ];
    }
}
