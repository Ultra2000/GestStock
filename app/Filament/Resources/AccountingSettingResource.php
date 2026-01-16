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
                // SECTION FRANCHISE TVA - EN PREMIER POUR VISIBILITÉ
                Forms\Components\Section::make('Régime Fiscal')
                    ->description('Configuration du régime de TVA de votre entreprise')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        Forms\Components\Toggle::make('is_vat_franchise')
                            ->label('Franchise en base de TVA')
                            ->helperText('Activez cette option si vous êtes en "Franchise en base de TVA" (Auto-entrepreneur, micro-entreprise). GestStock appliquera automatiquement un taux à 0% et ajoutera la mention obligatoire "Art. 293 B du CGI" sur tous vos documents.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('gray')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Mode Franchise TVA activé')
                                        ->body('Toutes vos factures et devis afficheront désormais TVA 0% avec la mention légale Art. 293 B du CGI.')
                                        ->success()
                                        ->duration(5000)
                                        ->send();
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('franchise_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-500 dark:text-gray-400 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <p class="font-semibold text-blue-700 dark:text-blue-300 mb-2">
                                        <span class="mr-1">ℹ️</span> Quand activer cette option ?
                                    </p>
                                    <ul class="list-disc list-inside space-y-1 ml-2">
                                        <li><strong>Micro-entrepreneurs & Freelances</strong> : Si vous ne dépassez pas les plafonds de CA et ne facturez pas de TVA</li>
                                        <li><strong>Associations</strong> : Pour les activités non lucratives exonérées</li>
                                    </ul>
                                    <p class="mt-3 font-semibold text-blue-700 dark:text-blue-300 mb-2">Ce que GestStock automatise :</p>
                                    <ul class="list-disc list-inside space-y-1 ml-2">
                                        <li><strong>Ventes & POS</strong> : Prix traités en "Net à payer", sans TVA</li>
                                        <li><strong>PDF</strong> : Mention légale "TVA non applicable, art. 293 B du CGI"</li>
                                        <li><strong>Dashboard</strong> : Widget TVA masqué (non applicable)</li>
                                    </ul>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record?->is_vat_franchise),

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
                    ->description('Numéros de comptes du Plan Comptable Général (6 chiffres minimum)')
                    ->schema([
                        Forms\Components\TextInput::make('account_customers')
                            ->label('Compte Clients (Classe 4)')
                            ->required()
                            ->default('411000')
                            ->regex('/^4[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte client doit commencer par 4 et contenir au moins 6 chiffres (ex: 411000)',
                            ])
                            ->helperText('Ex: 411000 - Doit commencer par 4'),

                        Forms\Components\TextInput::make('account_suppliers')
                            ->label('Compte Fournisseurs (Classe 4)')
                            ->required()
                            ->default('401000')
                            ->regex('/^4[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte fournisseur doit commencer par 4 et contenir au moins 6 chiffres (ex: 401000)',
                            ])
                            ->helperText('Ex: 401000 - Doit commencer par 4'),

                        Forms\Components\TextInput::make('account_bank')
                            ->label('Compte Banque (Classe 5)')
                            ->required()
                            ->default('512000')
                            ->regex('/^5[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte banque doit commencer par 5 et contenir au moins 6 chiffres (ex: 512000)',
                            ])
                            ->helperText('Ex: 512000 - Doit commencer par 5'),

                        Forms\Components\TextInput::make('account_cash')
                            ->label('Compte Caisse (Classe 5)')
                            ->required()
                            ->default('530000')
                            ->regex('/^5[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte caisse doit commencer par 5 et contenir au moins 6 chiffres (ex: 530000)',
                            ])
                            ->helperText('Ex: 530000 - Doit commencer par 5'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Plan Comptable - Comptes de Gestion')
                    ->schema([
                        Forms\Components\TextInput::make('account_sales')
                            ->label('Compte Ventes (Classe 7)')
                            ->required()
                            ->default('707000')
                            ->regex('/^7[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte ventes doit commencer par 7 et contenir au moins 6 chiffres (ex: 707000)',
                            ])
                            ->helperText('Ex: 707000 - Ventes de marchandises'),

                        Forms\Components\TextInput::make('account_purchases')
                            ->label('Compte Achats (Classe 6)')
                            ->required()
                            ->default('607000')
                            ->regex('/^6[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte achats doit commencer par 6 et contenir au moins 6 chiffres (ex: 607000)',
                            ])
                            ->helperText('Ex: 607000 - Achats de marchandises'),

                        Forms\Components\TextInput::make('account_discounts_granted')
                            ->label('Compte Remises accordées')
                            ->required()
                            ->default('709000')
                            ->regex('/^7[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte remises doit commencer par 7 et contenir au moins 6 chiffres (ex: 709000)',
                            ])
                            ->helperText('Ex: 709000 - Rabais, remises, ristournes accordés'),

                        Forms\Components\TextInput::make('account_discounts_received')
                            ->label('Compte Remises obtenues')
                            ->required()
                            ->default('609000')
                            ->regex('/^6[0-9]{5,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte remises doit commencer par 6 et contenir au moins 6 chiffres (ex: 609000)',
                            ])
                            ->helperText('Ex: 609000 - Rabais, remises, ristournes obtenus'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Comptes TVA')
                    ->schema([
                        Forms\Components\TextInput::make('account_vat_collected')
                            ->label('TVA Collectée')
                            ->required()
                            ->default('445710')
                            ->regex('/^4457[0-9]{2,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte TVA collectée doit commencer par 4457 (ex: 445710)',
                            ])
                            ->helperText('Ex: 445710 - TVA collectée')
                            ->disabled(fn ($get) => $get('is_vat_franchise')),

                        Forms\Components\TextInput::make('account_vat_deductible')
                            ->label('TVA Déductible')
                            ->required()
                            ->default('445660')
                            ->regex('/^4456[0-9]{2,}$/')
                            ->validationMessages([
                                'regex' => 'Le compte TVA déductible doit commencer par 4456 (ex: 445660)',
                            ])
                            ->helperText('Ex: 445660 - TVA déductible')
                            ->disabled(fn ($get) => $get('is_vat_franchise')),
                        
                        Forms\Components\Placeholder::make('vat_franchise_notice')
                            ->label('')
                            ->content('⚠️ Comptes TVA désactivés car vous êtes en Franchise de TVA')
                            ->visible(fn ($get) => $get('is_vat_franchise'))
                            ->columnSpanFull(),
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
                Tables\Columns\IconColumn::make('is_vat_franchise')
                    ->label('Franchise TVA')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

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
