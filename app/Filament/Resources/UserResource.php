<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
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

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Utilisateurs & Rôles';
    protected static ?string $modelLabel = 'Utilisateur';
    protected static ?string $pluralModelLabel = 'Utilisateurs';

    // Utiliser la relation 'companies' pour la multi-tenancy
    protected static ?string $tenantOwnershipRelationshipName = 'companies';

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de connexion')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText(fn (string $context): string => $context === 'edit' ? 'Laisser vide pour ne pas modifier' : ''),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rôles et permissions')
                    ->schema([
                        Forms\Components\CheckboxList::make('company_roles')
                            ->label('Rôles dans cette entreprise')
                            ->options(function () use ($tenant) {
                                if (!$tenant) return [];
                                return Role::where('company_id', $tenant->id)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->descriptions(function () use ($tenant) {
                                if (!$tenant) return [];
                                return Role::where('company_id', $tenant->id)
                                    ->pluck('description', 'id')
                                    ->toArray();
                            })
                            ->columns(2)
                            ->bulkToggleable()
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?User $record) use ($tenant) {
                                if ($record && $tenant) {
                                    $roleIds = $record->roles()
                                        ->wherePivot('company_id', $tenant->id)
                                        ->pluck('roles.id')
                                        ->toArray();
                                    $component->state($roleIds);
                                }
                            })
                            ->helperText('Sélectionnez un ou plusieurs rôles pour cet utilisateur'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_roles_display')
                    ->label('Rôles')
                    ->badge()
                    ->getStateUsing(function (User $record) use ($tenant) {
                        if (!$tenant) return [];
                        return $record->roles()
                            ->wherePivot('company_id', $tenant->id)
                            ->pluck('name')
                            ->toArray();
                    })
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'admin') => 'danger',
                        str_contains(strtolower($state), 'manager') || str_contains(strtolower($state), 'gestionnaire') => 'warning',
                        str_contains(strtolower($state), 'caissier') || str_contains(strtolower($state), 'cashier') => 'info',
                        default => 'success',
                    })
                    ->separator(', '),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(function () use ($tenant) {
                        if (!$tenant) return [];
                        return Role::where('company_id', $tenant->id)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) use ($tenant) {
                        if (!$data['value'] || !$tenant) return $query;
                        
                        return $query->whereHas('roles', function ($q) use ($data, $tenant) {
                            $q->where('roles.id', $data['value'])
                              ->where('model_has_roles.company_id', $tenant->id);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) use ($tenant) {
                        // Retirer les rôles de cette entreprise avant suppression
                        if ($tenant) {
                            $record->roles()->wherePivot('company_id', $tenant->id)->detach();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
