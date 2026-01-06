<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingSettingResource\Pages;
use App\Models\AccountingSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingSettingResource extends Resource
{
    protected static ?string $model = AccountingSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Paramètres Comptables';

    protected static ?string $modelLabel = 'Paramètres Comptables';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'entreprise')
                    ->description('Le SIREN et la raison sociale sont récupérés automatiquement depuis les paramètres de l\'entreprise')
                    ->schema([
                        Forms\Components\Placeholder::make('auto_siren')
                            ->label('SIREN (automatique)')
                            ->content(function () {
                                $company = filament()->getTenant();
                                if ($company && $company->siret) {
                                    $siren = substr(preg_replace('/[^0-9]/', '', $company->siret), 0, 9);
                                    return $siren . ' (extrait du SIRET: ' . $company->siret . ')';
                                }
                                return 'Non configuré - Veuillez renseigner le SIRET dans les paramètres de l\'entreprise';
                            })
                            ->helperText('Le SIREN est automatiquement extrait des 9 premiers chiffres du SIRET'),

                        Forms\Components\Placeholder::make('auto_company_name')
                            ->label('Raison sociale (automatique)')
                            ->content(fn () => filament()->getTenant()?->name ?? 'Non configurée')
                            ->helperText('Raison sociale récupérée depuis les paramètres de l\'entreprise'),

                        Forms\Components\TextInput::make('accounting_software')
                            ->label('Logiciel comptable')
                            ->default('GestStock')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('accounting_software_version')
                            ->label('Version')
                            ->default('1.0')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Plan Comptable - Comptes de Bilan')
                    ->description('Numéros de comptes du Plan Comptable Général')
                    ->schema([
                        Forms\Components\TextInput::make('account_customers')
                            ->label('Compte Clients (Classe 4)')
                            ->required()
                            ->default('411000')
                            ->helperText('Ex: 411000'),

                        Forms\Components\TextInput::make('account_suppliers')
                            ->label('Compte Fournisseurs (Classe 4)')
                            ->required()
                            ->default('401000')
                            ->helperText('Ex: 401000'),

                        Forms\Components\TextInput::make('account_bank')
                            ->label('Compte Banque (Classe 5)')
                            ->required()
                            ->default('512000')
                            ->helperText('Ex: 512000'),

                        Forms\Components\TextInput::make('account_cash')
                            ->label('Compte Caisse (Classe 5)')
                            ->required()
                            ->default('530000')
                            ->helperText('Ex: 530000'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Plan Comptable - Comptes de Gestion')
                    ->schema([
                        Forms\Components\TextInput::make('account_sales')
                            ->label('Compte Ventes (Classe 7)')
                            ->required()
                            ->default('707000')
                            ->helperText('Ex: 707000 - Ventes de marchandises'),

                        Forms\Components\TextInput::make('account_purchases')
                            ->label('Compte Achats (Classe 6)')
                            ->required()
                            ->default('607000')
                            ->helperText('Ex: 607000 - Achats de marchandises'),

                        Forms\Components\TextInput::make('account_discounts_granted')
                            ->label('Compte Remises accordées')
                            ->required()
                            ->default('709000')
                            ->helperText('Ex: 709000 - Rabais, remises, ristournes accordés'),

                        Forms\Components\TextInput::make('account_discounts_received')
                            ->label('Compte Remises obtenues')
                            ->required()
                            ->default('609000')
                            ->helperText('Ex: 609000 - Rabais, remises, ristournes obtenus'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Comptes TVA')
                    ->schema([
                        Forms\Components\TextInput::make('account_vat_collected')
                            ->label('TVA Collectée')
                            ->required()
                            ->default('445710')
                            ->helperText('Ex: 445710 - TVA collectée'),

                        Forms\Components\TextInput::make('account_vat_deductible')
                            ->label('TVA Déductible')
                            ->required()
                            ->default('445660')
                            ->helperText('Ex: 445660 - TVA déductible'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Codes Journaux')
                    ->description('Codes des journaux comptables (2-3 caractères)')
                    ->schema([
                        Forms\Components\TextInput::make('journal_sales')
                            ->label('Journal des Ventes')
                            ->required()
                            ->default('VTE')
                            ->maxLength(10)
                            ->helperText('Ex: VTE'),

                        Forms\Components\TextInput::make('journal_purchases')
                            ->label('Journal des Achats')
                            ->required()
                            ->default('ACH')
                            ->maxLength(10)
                            ->helperText('Ex: ACH'),

                        Forms\Components\TextInput::make('journal_bank')
                            ->label('Journal de Banque')
                            ->required()
                            ->default('BQ')
                            ->maxLength(10)
                            ->helperText('Ex: BQ'),

                        Forms\Components\TextInput::make('journal_cash')
                            ->label('Journal de Caisse')
                            ->required()
                            ->default('CAI')
                            ->maxLength(10)
                            ->helperText('Ex: CAI'),

                        Forms\Components\TextInput::make('journal_misc')
                            ->label('Journal Opérations Diverses')
                            ->required()
                            ->default('OD')
                            ->maxLength(10)
                            ->helperText('Ex: OD'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_customers')
                    ->label('Compte Clients')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('account_suppliers')
                    ->label('Compte Fournisseurs')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('journal_sales')
                    ->label('Journal Ventes')
                    ->badge(),

                Tables\Columns\TextColumn::make('journal_purchases')
                    ->label('Journal Achats')
                    ->badge(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccountingSettings::route('/'),
        ];
    }
}
