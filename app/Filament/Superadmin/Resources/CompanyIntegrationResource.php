<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\CompanyIntegrationResource\Pages;
use App\Filament\Superadmin\Resources\CompanyIntegrationResource\RelationManagers;
use App\Models\CompanyIntegration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyIntegrationResource extends Resource
{
    protected static ?string $model = CompanyIntegration::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Intégrations';
    protected static ?string $modelLabel = 'Intégration';
    protected static ?string $pluralModelLabel = 'Intégrations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuration générale')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label('Entreprise')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('service_name')
                            ->label('Service')
                            ->options([
                                'ppf' => 'PPF / Chorus Pro (Facturation électronique)',
                                'urssaf' => 'URSSAF',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(3),

                // Section PPF - Fournisseur (émission de factures)
                Forms\Components\Section::make('PPF - Compte Fournisseur')
                    ->description('Credentials pour envoyer des factures vers Chorus Pro')
                    ->schema([
                        Forms\Components\TextInput::make('settings.fournisseur_login')
                            ->label('Login compte technique')
                            ->placeholder('TECH_1_XXXXXX@cpro.fr')
                            ->helperText('Compte technique pour l\'API'),
                        Forms\Components\TextInput::make('settings.fournisseur_password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable(),
                        Forms\Components\TextInput::make('settings.fournisseur_siret')
                            ->label('SIRET Fournisseur')
                            ->placeholder('35068473658377')
                            ->maxLength(14),
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => $get('service_name') === 'ppf'),

                // Section PPF - API PISTE
                Forms\Components\Section::make('PPF - Credentials API PISTE')
                    ->description('Credentials OAuth de l\'application PISTE')
                    ->schema([
                        Forms\Components\TextInput::make('settings.client_id')
                            ->label('Client ID PISTE')
                            ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
                        Forms\Components\TextInput::make('settings.client_secret')
                            ->label('Client Secret PISTE')
                            ->password()
                            ->revealable(),
                        Forms\Components\TextInput::make('settings.api_key')
                            ->label('API Key (KeyId)')
                            ->password()
                            ->revealable(),
                        Forms\Components\Select::make('settings.environment')
                            ->label('Environnement')
                            ->options([
                                'sandbox' => 'Sandbox (Test)',
                                'production' => 'Production',
                            ])
                            ->default('sandbox'),
                    ])->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('service_name') === 'ppf'),

                // Section URSSAF
                Forms\Components\Section::make('URSSAF - Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('settings.urssaf_client_id')
                            ->label('Client ID'),
                        Forms\Components\TextInput::make('settings.urssaf_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable(),
                    ])->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('service_name') === 'urssaf'),

                // Tokens (gérés automatiquement)
                Forms\Components\Section::make('Tokens (gérés automatiquement)')
                    ->schema([
                        Forms\Components\TextInput::make('access_token')
                            ->label('Token d\'accès')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expire le')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('last_sync_at')
                            ->label('Dernière synchronisation')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('last_error')
                            ->label('Dernière erreur')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_name')
                    ->label('Service')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ppf' => 'success',
                        'urssaf' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ppf' => 'PPF / Chorus Pro',
                        'urssaf' => 'URSSAF',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('settings.environment')
                    ->label('Environnement')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'production' ? 'danger' : 'warning')
                    ->formatStateUsing(fn (?string $state): string => $state === 'production' ? 'PROD' : 'Sandbox'),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Dernière synchro')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Mis à jour le'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_name')
                    ->label('Service')
                    ->options([
                        'ppf' => 'PPF / Chorus Pro',
                        'urssaf' => 'URSSAF',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test_connection')
                    ->label('Tester')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (CompanyIntegration $record) {
                        // TODO: Implémenter le test de connexion
                        \Filament\Notifications\Notification::make()
                            ->title('Test de connexion')
                            ->body('Fonctionnalité à venir')
                            ->info()
                            ->send();
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
            'index' => Pages\ListCompanyIntegrations::route('/'),
            'create' => Pages\CreateCompanyIntegration::route('/create'),
            'edit' => Pages\EditCompanyIntegration::route('/{record}/edit'),
        ];
    }
}
