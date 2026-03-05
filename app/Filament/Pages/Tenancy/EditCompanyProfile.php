<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditCompanyProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Profil de l\'entreprise';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations Générales')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom / Raison Sociale')
                                    ->required()
                                    ->columnSpan(2),
                                Select::make('forme_juridique')
                                    ->label('Forme Juridique')
                                    ->options([
                                        'EI' => 'EI - Entreprise Individuelle',
                                        'EIRL' => 'EIRL',
                                        'EURL' => 'EURL',
                                        'SARL' => 'SARL',
                                        'SAS' => 'SAS',
                                        'SASU' => 'SASU',
                                        'SA' => 'SA',
                                        'SNC' => 'SNC',
                                        'SCI' => 'SCI',
                                        'SCOP' => 'SCOP',
                                        'Association' => 'Association',
                                    ])
                                    ->searchable()
                                    ->helperText('Obligatoire sur les factures'),
                                TextInput::make('capital_social')
                                    ->label('Capital Social')
                                    ->placeholder('10 000 €')
                                    ->helperText('Ex: 10 000 €'),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email(),
                                TextInput::make('phone')
                                    ->label('Téléphone'),
                                TextInput::make('address')
                                    ->label('Adresse du siège social')
                                    ->columnSpan(2),
                                TextInput::make('city')
                                    ->label('Ville'),
                                TextInput::make('zip_code')
                                    ->label('Code postal'),
                                TextInput::make('website')
                                    ->label('Site Web')
                                    ->url(),
                                Select::make('currency')
                                    ->label('Devise')
                                    ->options([
                                        'XOF' => 'XOF - Franc CFA (Afrique de l\'Ouest)',
                                        'XAF' => 'XAF - Franc CFA (Afrique Centrale)',
                                        'USD' => 'USD - Dollar Américain',
                                        'EUR' => 'EUR - Euro',
                                        'GBP' => 'GBP - Livre Sterling',
                                        'CHF' => 'CHF - Franc Suisse',
                                        'CAD' => 'CAD - Dollar Canadien',
                                    ])
                                    ->default('EUR'),
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('company-logos')
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Identifiants Légaux')
                    ->description('Numéros d\'immatriculation obligatoires sur vos factures.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('registration_number')
                                    ->label('SIREN (9 chiffres)')
                                    ->maxLength(9)
                                    ->helperText('Identifiant unique de l\'entreprise'),
                                TextInput::make('siret')
                                    ->label('SIRET (14 chiffres)')
                                    ->maxLength(14)
                                    ->helperText('Requis pour Factur-X, PPF et facture électronique'),
                                TextInput::make('tax_number')
                                    ->label('N° TVA Intracommunautaire')
                                    ->placeholder('FR XX XXXXXXXXX')
                                    ->helperText('Obligatoire si facture > 150 € HT'),
                                TextInput::make('code_naf')
                                    ->label('Code NAF / APE')
                                    ->placeholder('6201Z')
                                    ->maxLength(10)
                                    ->helperText('Code activité INSEE'),
                                TextInput::make('rcs_number')
                                    ->label('RCS (Registre du Commerce)')
                                    ->placeholder('RCS Paris B 123 456 789')
                                    ->helperText('Commerçants : ville + numéro'),
                                TextInput::make('rm_number')
                                    ->label('RM (Répertoire des Métiers)')
                                    ->placeholder('RM 75 123 456 789')
                                    ->helperText('Artisans uniquement'),
                            ]),
                    ]),

                Section::make('Pied de page & Notes')
                    ->schema([
                        Textarea::make('footer_text')
                            ->label('Texte de pied de page (Factures)')
                            ->helperText('Apparaît en bas de chaque facture PDF. Laissez vide pour le texte par défaut.')
                            ->rows(3),
                    ]),

                Section::make('Fonctionnalités')
                    ->description('Activez ou désactivez les modules selon vos besoins.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('settings.modules.pos')
                                    ->label('Point de Vente (Caisse)')
                                    ->default(true),
                                Toggle::make('settings.modules.stock')
                                    ->label('Gestion de Stock (Entrepôts, Transferts)')
                                    ->default(true),
                                Toggle::make('settings.modules.hr')
                                    ->label('Ressources Humaines (Employés, Paie)')
                                    ->default(true),
                                Toggle::make('settings.modules.accounting')
                                    ->label('Comptabilité')
                                    ->default(true),
                                Toggle::make('settings.modules.banking')
                                    ->label('Gestion Bancaire')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Modèle de facture PDF')
                    ->description('Choisissez l\'apparence de vos factures de vente générées en PDF.')
                    ->schema([
                        ViewField::make('settings.invoice_template')
                            ->view('filament.forms.invoice-template-selector'),
                    ]),
            ]);
    }
}
