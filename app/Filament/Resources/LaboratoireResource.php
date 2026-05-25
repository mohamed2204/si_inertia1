<?php
namespace App\Filament\Resources;

use App\Enums\SectionType;
use App\Filament\Resources\LaboratoireResource\Pages;
use App\Models\Laboratoire;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LaboratoireResource extends Resource
{
    protected static ?string $model = Laboratoire::class;

    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Gestion des Labs';
    protected static ?int $navigationSort     = 1;

    public static function form(Form $form): Form
    {
        // return $form->schema([
        //     Forms\Components\Section::make('Détails du Laboratoire')
        //         ->schema([
        //             Forms\Components\Select::make('sous_departement_id')
        //                 ->relationship('sousDepartement', 'nom')
        //                 ->label('Sous-Département')
        //                 ->required()
        //                 ->searchable()
        //                 ->preload(),
        //             Forms\Components\TextInput::make('nom')
        //                 ->label('Nom du Labo')
        //                 ->required()
        //                 ->maxLength(255),
        //             Forms\Components\TextInput::make('code')
        //                 ->label('Code/Abbréviation')
        //                 ->placeholder('ex: L-BIO'),
        //             Forms\Components\Toggle::make('est_actif')
        //                 ->label('En service')
        //                 ->default(true),
        //         ])->columns(2)
        // ]);
        return $form->schema([
            // Section infos générales (Nom, etc.)
            Section::make('Informations du Laboratoire')
                ->schema([
                    TextInput::make('nom')->required(),
                    Select::make('sous_departement_id')
                        ->relationship('sousDepartement', 'nom')
                        ->required(),
                ]),

            // SECTION DES REQUIS (Le fameux FormRequis)
            Section::make('Configuration des Requis')
                ->description('Définissez les postes et l\'ordre pour ce laboratoire')
                ->schema([
                    TableRepeater::make('labRequis')
                        ->relationship('labRequis')
                        ->headers([
                            Header::make('role_tache_id')->label('Rôle')->width('40%'),
                            Header::make('section')->label('Section')->width('30%'),
                            Header::make('nombre_requis')->label('Quota')->width('20%'),
                        ])
                        ->schema([
                            Select::make('role_tache_id')
                                ->relationship('roleTache', 'libelle')
                                ->required()
                                ->disableLabel(), // Méthode native pour masquer le label

                            Select::make('section')
                                ->options(SectionType::class)
                                ->required()
                                ->disableLabel(),

                            TextInput::make('nombre_requis')
                                ->numeric()
                                ->default(1)
                                ->disableLabel(),
                        ])
                        ->reorderable('ordre')
                        ->addActionLabel('Ajouter un poste')
                        ->emptyLabel('Aucun poste configuré'), // Ici, la méthode existe bien
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('sousDepartement.nom')
                ->label('Sous-Département')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('nom')
                ->label('Laboratoire')
                ->sortable()
                ->searchable(),
            Tables\Columns\IconColumn::make('est_actif')
                ->label('Statut')
                ->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('sous_departement_id')
                ->relationship('sousDepartement', 'nom')
                ->label('Filtrer par Sous-Département'),
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
            'index'  => Pages\ListLaboratoires::route('/'),
            'create' => Pages\CreateLaboratoire::route('/create'),
            'edit'   => Pages\EditLaboratoire::route('/{record}/edit'),
        ];
    }
}
