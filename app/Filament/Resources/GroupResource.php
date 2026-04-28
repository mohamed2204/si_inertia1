<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section; // Préférable à Card dans v3
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group'; // Plus adapté pour des groupes

    protected static ?string $navigationGroup = 'Configuration'; // Pour mieux organiser votre menu "si"
    protected static ?int $navigationSort = 3; // En 2 position

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations du Groupe') // Correction ici
                ->description('Définissez le nom et le périmètre du groupe')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du Groupe')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('code', \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($state)))),

                        TextInput::make('code')
                            ->label('Code Identification')
                            ->dehydrated() // Permet d'envoyer la donnée même si le champ est disabled
                            ->required()
                            ->unique(ignoreRecord: true),

                        Select::make('sousDepartements')
                            ->label('Sous-Départements gérés')
                            ->multiple()
                            ->relationship('sousDepartements', 'nom') // Assurez-vous que la relation existe dans le modèle Group
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->copyable()
                    ->sortable(),

                TextColumn::make('sous_departements_count')
                    ->label('Sous-Dép.')
                    ->counts('sousDepartements')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
