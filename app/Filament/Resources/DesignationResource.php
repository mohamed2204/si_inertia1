<?php
namespace App\Filament\Resources;

use App\Enums\SectionType;
use App\Filament\Resources\DesignationResource\Pages;
use App\Models\Designation;
use App\Models\Laboratoire;
use App\Models\LabRequis;
use App\Models\Membre;
use App\Models\SousDepartement;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

// Vérifiez l'import si vous utilisez des objets Header
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

// Si vous utilisez des boutons

class DesignationResource extends Resource
{
    protected static ?string $model = Designation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Gestion des Labs';
    protected static ?int $navigationSort     = 3;

    public static function remplirLesOnglets(Set $set, Get $get, $sousDeptId): void
    {
        //dd($sousDeptId);
        if (! $sousDeptId) {
            return;
        }

        // 1. On récupère les laboratoires liés à ce sous-département
        $labs = Laboratoire::where('sous_departement_id', $sousDeptId)->get();

        // 2. Pour chaque labo, on injecte les requis dans la clé du repeater correspondante
        foreach ($labs as $lab) {
            $key = 'items_lab_' . $lab->id;

            // --- LA PROTECTION ---
            // Si le repeater contient déjà des données (mode Edit), on ne fait rien
            // On ne remplit que si c'est vide
            if (blank($get($key))) {
                $requis = static::getRequisTableRepeater($lab->id, 'jour');
                $set($key, $requis);
                Log::info("Initialisation auto pour lab {$lab->id}");
            } else {
                Log::info("Conservation des données existantes pour lab {$lab->id}");
            }
        }
//        foreach ($labs as $lab) {
//            // On appelle votre méthode statique qui doit retourner un tableau
//            // Format attendu : [['role_tache_id' => 1, 'quantite' => 2], ...]
//            $requis = static::getRequisTableRepeater($lab->id, 'jours');
//
//            // On remplit le repeater spécifique à ce labo
//            $set('items_lab_' . $lab->id, $requis);
//            // Log dans storage/logs/laravel.log
//            Log::info("Données pour lab {$lab->id}:", $requis);
//        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- LIGNE DU HAUT : PILOTAGE ET STATUT ---
//                Grid::make(3) // 3 colonnes pour équilibrer
//                ->schema([
//                    // BLOC GAUCHE : SÉLECTION (Occupe 2 colonnes)
//                    Section::make('Contexte de la Désignation')
//                        ->schema([
//                            Grid::make(2)->schema([
//                                DatePicker::make('date_debut')
//                                    ->label('Semaine du')
//                                    ->required()
//                                    ->live(),
//                                Select::make('departement_id')
//                                    ->label('Département')
//                                    ->options(\App\Models\Departement::pluck('nom', 'id'))
//                                    ->live(),
//                            ]),
//                            Select::make('sous_departement_id')
//                                ->label('Sous-Département')
//                                ->options(fn(Get $get) => SousDepartement::where('departement_id', $get('departement_id'))->pluck('nom', 'id'))
//                                ->live()
//                                ->required()
//                                // --- 1. ACTION AU CHARGEMENT (MODE EDIT) ---
//                                ->afterStateHydrated(function (Set $set, Get $get, $record) {
//                                    // Au lieu d'utiliser $state (qui est vide), on interroge le modèle $record
//                                    if (!$record || !$record->laboratoire) return;
//
//                                    $sousDeptId = $record->laboratoire->sous_departement_id;
//
//                                    if ($sousDeptId) {
//                                        // 1. On force la valeur du Select lui-même
//                                        $set('sous_departement_id', $sousDeptId);
//
//                                        // 2. On déclenche le remplissage des onglets
//                                        static::remplirLesOnglets($set, $get, $sousDeptId);
//                                    }
//                                })
//                                // --- 2. ACTION AU CHANGEMENT MANUEL (MODE CREATE/EDIT) ---
//                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
//                                    if (!$state) return;
//                                    // Lors d'un changement manuel, on veut souvent vider et remplir
//                                    // On peut forcer le vidage si nécessaire avant l'appel
//                                    static::remplirLesOnglets($set, $get, $state);
//                                })
//                        ])
//                        ->columnSpan(2),
//
//                    // BLOC DROITE : STATUT (Occupe 1 colonne)
//
//                    Section::make('Statut de la Sous-Département')
//                        ->description('Progression hebdomadaire')
//                        ->schema([
//                            Placeholder::make('progression_stats')
//                                ->label('')
//                                ->content(fn(Get $get) => view('filament.components.stats-progression', [
//                                    'stats' => static::getProgressionData($get('sous_departement_id'), $get('date_debut'))
//                                ])),
//                        ])
//                        ->columnSpan(1)
//                        // --- AJOUT DES CLASSES CSS ICI ---
//                        ->extraAttributes([
//                            'style' => 'max-height: 250px; overflow-y: auto;', // Ajustez les 250px selon la hauteur de votre bloc gauche
//                            'class' => 'scrollbar-thin scrollbar-thumb-gray-300' // Optionnel : pour un scroll plus discret
//                        ]),
//                ]),

                Grid::make(3)
                    ->schema([
                        // BLOC GAUCHE : CONFIGURATION (2 colonnes)
                        Grid::make(1)
                            ->schema([
                                Section::make('Paramètres de la Désignation')
                                    ->description('Cliquez pour modifier la période ou le secteur')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->collapsible()
                                    ->collapsed()        // Fermé par défaut pour gagner de l'espace
                                    ->persistCollapsed() // Garde l'état lors du rechargement
                                    ->schema([
                                        Grid::make(2)->schema([
                                            DatePicker::make('date_debut')
                                                ->label('Semaine du')
                                                ->disabled(fn($record) => $record !== null) // Désactivé si on édite
                                                ->hint(fn($record) => $record ? 'Modification verrouillée' : null)
                                                ->hintIcon(fn($record) => $record ? 'heroicon-m-lock-closed' : null)
                                                ->hintColor('warning')
                                                ->required()
                                                ->live(),
                                            Select::make('departement_id')
                                                ->label('Département')
                                                ->disabled(fn($record) => $record !== null) // Désactivé si on édite
                                                ->hint(fn($record) => $record ? 'Modification verrouillée' : null)
                                                ->hintIcon(fn($record) => $record ? 'heroicon-m-lock-closed' : null)
                                                ->hintColor('warning')
                                                ->options(\App\Models\Departement::pluck('nom', 'id'))
                                                ->live(),
                                        ]),
                                        Select::make('sous_departement_id')
                                            ->label('Sous-Département')
                                            ->options(fn(Get $get) => SousDepartement::where('departement_id', $get('departement_id'))->pluck('nom', 'id'))
                                            ->live()
                                            ->hint(fn($record) => $record ? 'Modification verrouillée' : null)
                                            ->hintIcon(fn($record) => $record ? 'heroicon-m-lock-closed' : null)
                                            ->hintColor('warning')
                                            ->disabled(fn($record) => $record !== null) // Désactivé si on édite
                                            ->required()
                                            ->afterStateHydrated(function (Set $set, Get $get, $record) {
                                                if (! $record || ! $record->laboratoire) {
                                                    return;
                                                }

                                                $sousDeptId = $record->laboratoire->sous_departement_id;
                                                if ($sousDeptId) {
                                                    $set('sous_departement_id', $sousDeptId);
                                                    static::remplirLesOnglets($set, $get, $sousDeptId);
                                                }
                                            })
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if (! $state) {
                                                    return;
                                                }

                                                static::remplirLesOnglets($set, $get, $state);
                                            }),
                                    ])
                                    ->columnSpan(2),
                            ])
                            ->columnSpan(2),

                        // BLOC DROITE : STATUTS (1 colonne)
                        // On le garde hors de l'accordéon pour qu'il soit toujours visible
                        Section::make('Progression de la Sous-Département')
                            ->description('Cliquez pour voir le progression de la saisie')
                            ->icon('heroicon-o-chart-pie')
                            ->schema([
                                Placeholder::make('progression_stats')
                                    ->hiddenLabel()
                                    ->content(fn(Get $get) => view('filament.components.stats-progression', [
                                        'stats' => static::getProgressionData($get('sous_departement_id'), $get('date_debut')),
                                    ])),
                            ])
                            ->columnSpan(1)
                            ->collapsible()
                            ->collapsed()        // Fermé par défaut pour gagner de l'espace
                            ->persistCollapsed() // Garde l'état lors du rechargement
                            ->extraAttributes([
                                'class' => 'h-full shadow-sm border-t-4 border-t-primary-500',
                            ]),
                    ]),
                // --- BLOC DU BAS : LES ONGLETS DE LABORATOIRES ---
                Tabs::make('Laboratoires')
                    ->tabs(function (Get $get) {
                        $sousDeptId = $get('sous_departement_id');

                        // On définit la section ici pour qu'elle soit disponible dans le "use"
                        $section = SectionType::JOUR->value; //'jour';

                        if (! $sousDeptId) {
                            return [
                                Tabs\Tab::make('Info')
                                    ->icon('heroicon-m-information-circle')
                                    ->schema([
                                        Placeholder::make('aide')->content('Veuillez sélectionner un sous-département pour charger les laboratoires.'),
                                    ]),
                            ];
                        }

                        $labs = Laboratoire::where('sous_departement_id', $sousDeptId)->get();

                        return $labs->map(function ($lab) use ($section) {
                            // On capture l'ID dans une variable locale pour s'assurer qu'elle ne change pas
                            $currentLabId  = $lab->id;
                            $currentLabNom = $lab->nom;

                            return Tabs\Tab::make($currentLabNom)
                                ->schema([
                                    static::getLabTableRepeater($currentLabId, $section),

                                    Actions::make([
                                        Actions\Action::make('save_lab_' . $currentLabId)
                                            ->label('Enregistrer ' . $currentLabNom)
                                            ->visible(fn($record) => $record !== null) // Masqué si nouveau record
                                            ->action(function ($record, Get $get) use ($currentLabId) {
                                                // Si $record est null ici, c'est que Filament ne l'a pas injecté
                                                if (! $record) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->warning()
                                                        ->title('Action impossible')
                                                        ->body('Veuillez d\'abord enregistrer la désignation principale.')
                                                        ->send();
                                                    return;
                                                }
                                                //dump($currentLabId);
                                                static::saveSingleLab($record, $currentLabId, $get('items_lab_' . $currentLabId));
                                            }),
                                    ]),
                                ]);
                        })->toArray();
                    })
                    ->columnSpanFull()
                    ->persistTabInQueryString(), // Pratique pour ne pas perdre l'onglet au refresh
            ]);
    }

    public static function getRequisTableRepeater($labId, $type = 'jour'): array
    {
        // On va chercher dans la table des besoins/requis
        // (A adapter selon le nom exact de votre modèle de configuration)

        $requis = LabRequis::where('laboratoire_id', $labId)
            ->with('roleTache')
            ->get()
        // On trie la collection par l'ordre défini dans la relation
            ->sortBy(fn($item) => $item->roleTache?->ordre ?? 999);

        //dd($requis);

        return $requis
            ->map(function ($item) {
                return [
                    // On utilise 'libelle' car c'est le nom dans votre table role_taches
                    'role_libelle'  => $item->roleTache?->libelle ?? 'Sans libellé',

                    // On utilise 'nombre_requis' comme vu dans votre dump
                    'nombre_requis' => $item->nombre_requis,

                    // Identifiants techniques
                    'role_tache_id' => $item->role_tache_id,
                    'section'       => $item->section, // ex: "remplacants"

                    // Initialisation pour le Select Multiple de Filament
                    'membres'       => [],
                ];
            })
            ->toArray();
    }

    protected static function getProgressionData($sousDeptId, $date): array
    {
        $laboratoires = Laboratoire::where('sous_departement_id', $sousDeptId)->get();
        $stats        = ['labs' => [], 'total_global' => 0];

        $totalRequisGlobal = 0;
        $totalSaisisGlobal = 0;

        foreach ($laboratoires as $lab) {
            // Somme des requis pour ce labo
            $totalRequis = LabRequis::where('laboratoire_id', $lab->id)->sum('nombre_requis');

            // Somme des membres déjà affectés (via la table pivot ou le JSON)
            $saisis = \DB::table('designation_items')
                ->join('designations', 'designations.id', '=', 'designation_items.designation_id')
                ->where('designations.laboratoire_id', $lab->id)
                ->where('designations.date_debut', $date)
                ->count();

            $pourcentageLab = $totalRequis > 0 ? min(round(($saisis / $totalRequis) * 100), 100) : 0;

            $stats['labs'][] = (object) [
                'nom'        => $lab->nom,
                'percentage' => $pourcentageLab,
                'saisis'     => $saisis,
                'total'      => $totalRequis,
                'complet'    => ($totalRequis > 0 && $saisis >= $totalRequis),
            ];

            $totalRequisGlobal += $totalRequis;
            $totalSaisisGlobal += $saisis;
        }

        $stats['total_global'] = $totalRequisGlobal > 0
            ? round(($totalSaisisGlobal / $totalRequisGlobal) * 100)
            : 0;

        //dd($stats);
        return $stats;
    }

    /**
     * Accessor pour calculer la progression dynamiquement
     */
    /**
     * Générateur de TableRepeater Standardisé
     */
    protected static function getLabTableRepeater(int $labId, string $section): TableRepeater
    {
        // Le nom doit inclure l'ID du labo pour isoler les données de chaque onglet
        return TableRepeater::make('items_lab_' . $labId)
            ->headers([
                Header::make('role_libelle')
                    ->label('Poste / Jour')
                    ->width('200px'),
                Header::make('membres')
                    ->label('Membres affectés'),
            ])
            ->emptyLabel('Aucun requis défini pour ce laboratoire')
            ->afterStateHydrated(function (TableRepeater $component, $state) {
                if (blank($state)) {
                    return;
                }

                // On force Filament à s'assurer que chaque ligne est bien formatée
                $component->state($state);
            })
            ->schema([
                // On garde l'ID caché pour la sauvegarde technique
                Hidden::make('role_tache_id'),
                Hidden::make('date_effective'), // Important pour la DB !
                Placeholder::make('role_info')
                    ->hiddenLabel() // Supprime le label au-dessus
                    ->content(fn($get) => new \Illuminate\Support\HtmlString(
                        "<div class='py-2'>" .
                        "<div class='font-bold text-gray-900'>{$get('role_libelle')}</div>" .
                        "<div class='text-xs text-gray-500'>{$get('date_display')}</div>" .
                        "</div>"
                    )),

                // Affichage du libellé du rôle (ex: "Technicien de garde")
//                TextInput::make('role_libelle')
//                    ->disabled()
//                    //->dehydrated(false)
//                    ->extraAttributes(['class' => 'font-bold border-none shadow-none bg-transparent']),

                // Sélection Multiple des membres

                // ************* TEMP POUR FILTER PAR ROLE *********************

//                Select::make('membres')
//                    ->multiple()
//                    ->options(function (Get $get) {
//                        // On récupère le sous-département sélectionné dans le formulaire parent
//                        // "../../" permet de remonter du Repeater vers le formulaire principal
//                        $sousDeptId = $get('../../sous_departement_id');
//
//                        if (!$sousDeptId) return [];
//
//                        return Membre::whereHas('groupes.sousDepartements', function ($q) use ($sousDeptId) {
//                            $q->where('id', $sousDeptId);
//                        })->pluck('nom', 'id');
//                    })
                // ************* TEMP POUR FILTER PAR ROLE *********************
                Select::make('membres')
                    ->hiddenLabel()
                    ->multiple()
                    ->options(Membre::all()->pluck('nom', 'id'))
                // Affiche "Requis: X" juste au dessus du select à droite
                    ->hint(fn($get) => "Quota: {$get('nombre_requis')}")
                    ->hintColor('primary')
                    ->hintIcon('heroicon-m-users')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull() // Optionnel selon votre largeur
                    ->maxItems(
                        fn(Get $get) => static::getQuota($labId, $get('role_tache_id'))
                    )
                    ->rules([
                        fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $labId) {
                            $quota = (int) $get('nombre_requis');
                            if (count($value) < $quota) {
                                $fail("Il faut au moins {$quota} agent(s).");
                            }
                        },
                    ]),

//                Select::make('membres')
//                    ->multiple()
//                    ->label('Sélectionner les agents')
//                    ->options(Membre::all()->pluck('nom', 'id'))
////                    ->options([
////                        '1' => 'Test Membre 1',
////                        '2' => 'Test Membre 2',
////                    ])
//                    ->afterStateHydrated(function (Select $component, $state) {
//                        // Force la conversion en tableau de strings si Filament reçoit autre chose
//                        if (is_array($state)) {
//                            $component->state(collect($state)->map(fn($id) => (string)$id)->toArray());
//                        }
//                    })
//                    ->required()
//                    ->preload() // Important pour la réactivité sur VDI
//                    ->searchable()
//                    ->maxItems(
//                        fn(Get $get) => static::getQuota($labId, $get('role_tache_id'))
//                    )
//                    ->rules([
//                        fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $labId) {
//                            $quota = static::getQuota($labId, $get('role_tache_id'));
//                            if (count($value) < $quota) {
//                                $fail("Attention : ce poste nécessite {$quota} agent(s).");
//                            }
//                        },
//                    ]),
            ])
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull();
    }

    public static function getQuota($labId, $roleTacheId): int
    {
        // On cherche dans la table des requis la configuration spécifique
        $requis = LabRequis::where('laboratoire_id', $labId)
            ->where('role_tache_id', $roleTacheId)
            ->first();

        // On retourne le nombre requis, ou 1 par défaut si non défini
        return $requis ? (int) $requis->nombre_requis : 1;
    }

    public static function saveSingleLab($record, $labId, array $items): void
    {
        $keptIds = [];

        foreach ($items as $item) {
            $roleId     = $item['role_tache_id'] ?? null;
            $membresIds = $item['membres'] ?? [];

            // On détermine la date de l'item.
            // Si le repeater contient une date spécifique par ligne, on l'utilise.
            // Sinon, on calcule selon le type de rôle (ex: Responsable = date_debut).
            $dateEffective = $record->date_debut;

            if (! $roleId) {
                continue;
            }

            foreach ($membresIds as $membreId) {
                $entry = $record->items()->updateOrCreate(
                    [
                        'designation_id' => $record->id,
                        'laboratoire_id' => $labId,
                        'role_tache_id'  => $roleId,
                        'membre_id'      => $membreId,
                    ],
                    [
                        // Ici on applique la date précise
                        'date_effective' => $dateEffective,
                    ]
                );
                $keptIds[] = $entry->id;
            }
        }

        $record->items()
            ->where('laboratoire_id', $labId)
            ->whereNotIn('id', $keptIds)
            ->delete();
        // 4. Notification de succès pour le confort sur VDI
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Laboratoire enregistré')
            ->body('Les affectations ont été mises à jour avec succès.')
            ->send();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_debut')
                    ->label('Semaine')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('laboratoire.nom')
                    ->label('Laboratoire'),
                Tables\Columns\TextColumn::make('statut')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'brouillon'           => 'warning',
                        'en_attente_decision' => 'info',
                        'valide'              => 'success',
                        'conflit'             => 'danger',
                    }),
            ])
            ->actions([
                // 1. Permet la lecture seule (Visible par tous ceux qui ont 'view')
                Tables\Actions\ViewAction::make(),
                // 2. Permet l'édition (Masqué automatiquement par la Policy si l'user est Lecteur)
                Tables\Actions\EditAction::make(),
                // 3. Votre action personnalisée de validation
                Tables\Actions\Action::make('Valider')
//                    ->action(fn(Designation $record) => $record
//                        ->update(['statut' => 'valide']))
                    ->action(fn(Designation $record) => $record->update(['statut' => 'valide']))
                    ->visible(fn(Designation $record) => auth()->user()->can('publish_designation'))
                    ->requiresConfirmation(),
                //->visible(fn() => auth()->user()->hasRole('admin')),
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
            'index'  => Pages\ListDesignations::route('/'),
            'create' => Pages\CreateDesignation::route('/create'),
            'edit'   => Pages\EditDesignation::route('/{record}/edit'),
        ];
    }

    // Dans DesignationResource.php
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('designation:admin')) {
            return parent::getEloquentQuery();
        }

        // On récupère les IDs de tous les sous-départements gérés par les groupes de l'user
        $subDeptIds = $user->groups->flatMap->sousDepartements->pluck('id');

        return parent::getEloquentQuery()->whereHas('laboratoire', function ($query) use ($subDeptIds) {
            $query->whereIn('sous_departement_id', $subDeptIds);
        });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // --- LIGNE DU HAUT : CONTEXTE ET STATUT ---
                \Filament\Infolists\Components\Grid::make(3)
                    ->schema([
                        // BLOC GAUCHE : INFOS GÉNÉRALES
                        \Filament\Infolists\Components\Section::make('Contexte de la Désignation')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(2)->schema([
                                    TextEntry::make('date_debut')
                                        ->label('Semaine du')
                                        ->date('d/m/Y')
                                        ->icon('heroicon-o-calendar'),

                                    TextEntry::make('departement.nom')
                                        ->label('Département')
                                        ->weight('bold'),
                                ]),

                                TextEntry::make('sousDepartement.nom')
                                    ->label('Sous-Département')
                                    ->color('primary'),
                            ])
                            ->columnSpan(2),

                        // BLOC DROITE : STATUT (Utilisation du même composant Blade)
                        \Filament\Infolists\Components\Section::make('Statut du Sous-Département')
                            ->description('Progression hebdomadaire')
                            ->schema([
                                ViewEntry::make('progression_stats')
                                    ->label('')
                                    ->view('filament.components.stats-progression')
                                    ->viewData([
                                        'stats' => function ($record) {
                                            // On vérifie que le record existe pour éviter de lire sur null
                                            if (! $record) {
                                                return ['labs' => [], 'total_global' => 0];
                                            }

                                            // On force l'appel de l'accessor et on retourne le résultat (le tableau)
                                            return $record->progression_stats;
                                        },
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),

                // --- BLOC DU BAS : ONGLETS DES LABORATOIRES ---
                \Filament\Infolists\Components\Tabs::make('Laboratoires')
                    ->tabs(function ($record) {
                        // Si pas de record ou pas de sous-dept, on affiche une info vide
                        if (! $record || ! $record->sous_departement_id) {
                            return [
                                \Filament\Infolists\Components\Tabs\Tab::make('Info')
                                    ->schema([
                                        TextEntry::make('info')->default('Aucune donnée chargée.'),
                                    ]),
                            ];
                        }

                        // On récupère les labos liés au sous-département de la désignation
                        $labs = \App\Models\Laboratoire::where('sous_departement_id', $record->sous_departement_id)->get();

                        return $labs->map(function ($lab) use ($record) {
                            return Tabs\Tab::make($lab->nom)
                                ->schema([
                                    // On remplace le TableRepeater par un RepeatableEntry (version lecture seule)
                                    RepeatableEntry::make('items_lab_' . $lab->id)
                                        ->label('Affectations')
                                    // Important : On doit formater les données pour que l'Infolist les trouve
                                    // car elles ne sont pas directement en base sous ce nom de clé
                                        ->state(function () use ($record, $lab) {
                                            // On réutilise votre logique de récupération des données
                                            return static::getRequisPourView($record, $lab->id);
                                        })
                                        ->schema([
                                            \Filament\Infolists\Components\Grid::make(2)->schema([
                                                TextEntry::make('role_libelle')
                                                    ->label('Poste / Jour')
                                                    ->weight('bold'),

                                                TextEntry::make('membres_noms')
                                                    ->label('Membres affectés')
                                                    ->badge()
                                                    ->separator(',')
                                                    ->color('success'),
                                            ]),
                                        ])
                                        ->columns(1),
                                ]);
                        })->toArray();
                    })
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Méthode d'aide pour formater les données pour la View
     */
    protected static function getRequisPourView($record, $labId): array
    {
        // 1. Récupérer les requis du labo
        $requis = \App\Models\LabRequis::where('laboratoire_id', $labId)
            ->with('roleTache')
            ->get();

        return $requis->map(function ($req) use ($record, $labId) {
            // 2. Récupérer les noms des membres affectés pour ce rôle précis dans cette désignation
            $nomsMembres = \DB::table('designation_items')
                ->join('members', 'members.id', '=', 'designation_items.member_id')
                ->where('designation_items.designation_id', $record->id)
                ->where('designation_items.laboratoire_id', $labId)
                ->where('designation_items.role_tache_id', $req->role_tache_id)
                ->pluck('members.nom')
                ->toArray();

            return [
                'role_libelle' => $req->roleTache?->libelle ?? 'Poste inconnu',
                'membres_noms' => $nomsMembres, // Sera affiché sous forme de badges
            ];
        })->toArray();
    }

}
