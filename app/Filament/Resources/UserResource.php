<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Utilisateurs';
    protected static bool $shouldRegisterNavigation = true;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Utilisateur')
                    ->tabs([
                        Tabs\Tab::make('Identité')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Nom'),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Email'),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    //->required(fn ($record) => $record === null)
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                    ->dehydrated(fn($state) => filled($state))
                                    ->maxLength(255)
                                    ->same('password_confirmation')
                                    ->placeholder('Mot de passe'),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->password()
                                    ->required(fn($record) => $record === null)
                                    ->dehydrated(false) // ⬅️ NE PAS sauvegarder
                                    ->maxLength(255)
                                    ->placeholder('Confirmation Mot de passe'),
                            ])->columns(2),

                        Tabs\Tab::make('Affectations & Droits')
                            ->schema([
                                Section::make('Périmètre Géographique')
                                    ->description('Affectez cet utilisateur à un ou plusieurs sous-départements.')
                                    ->schema([
                                        Select::make('groups')
                                            ->label('Unités / Sous-Départements')
                                            ->multiple()
                                            ->relationship('groups', 'name')
                                            ->preload()
                                            ->required(),
                                    ]),

                                Section::make('Permissions (Shield)')
                                    ->description('Choisissez le rôle fonctionnel (Lecteur, Éditeur, etc.)')
                                    ->schema([
                                        Select::make('roles')
                                            ->label('Rôles de sécurité')
                                            ->multiple()
                                            ->relationship('roles', 'name')
                                            ->preload(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
//        return $form
//            ->schema([
//                Forms\Components\TextInput::make('name')
//                    ->required()
//                    ->maxLength(255)
//                    ->placeholder('Nom'),
//                Forms\Components\TextInput::make('email')
//                    ->email()
//                    ->required()
//                    ->maxLength(255)
//                    ->unique(ignoreRecord: true)
//                    ->placeholder('Email'),
//                Forms\Components\TextInput::make('password')
//                    ->password()
//                    //->required(fn ($record) => $record === null)
//                    ->required(fn (string $context): bool => $context === 'create')
//                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
//                    ->dehydrated(fn ($state) => filled($state))
//                    ->maxLength(255)
//                    ->same('password_confirmation')
//                    ->placeholder('Mot de passe'),
//                Forms\Components\TextInput::make('password_confirmation')
//                    ->password()
//                    ->required(fn ($record) => $record === null)
//                    ->dehydrated(false) // ⬅️ NE PAS sauvegarder
//                    ->maxLength(255)
//                    ->placeholder('Confirmation Mot de passe'),
//                Forms\Components\Select::make('roles')
//                    ->relationship('roles', 'name')
//                    ->multiple()
//                    ->preload()
//                    ->searchable()
//            ]);


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                //Tables\Columns\TextColumn::make('roles')->badge(), // Transforme le texte en badge pour un look plus pro,
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôle')
                    ->badge() // Transforme le texte en badge pour un look plus pro
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\TextColumn::make('updated_at')

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public
    static function getRelations(): array
    {
        return [
            //
        ];
    }

    public
    static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
