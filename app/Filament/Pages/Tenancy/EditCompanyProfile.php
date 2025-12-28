<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        TextInput::make('name')
                            ->label('Nom de l\'entreprise')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('phone')
                            ->label('Téléphone'),
                        TextInput::make('address')
                            ->label('Adresse'),
                        TextInput::make('website')
                            ->label('Site Web')
                            ->url(),
                        TextInput::make('tax_number')
                            ->label('Numéro Fiscal (TVA Intra)'),
                        TextInput::make('registration_number')
                            ->label('SIREN (9 chiffres)'),
                        TextInput::make('siret')
                            ->label('SIRET (14 chiffres)')
                            ->helperText('Requis pour Factur-X / PPF'),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('company-logos'),
                        Textarea::make('footer_text')
                            ->label('Texte de pied de page (Factures)'),
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
                                'AUD' => 'AUD - Dollar Australien',
                                'JPY' => 'JPY - Yen Japonais',
                                'CNY' => 'CNY - Yuan Chinois',
                                'INR' => 'INR - Roupie Indienne',
                                'BRL' => 'BRL - Real Brésilien',
                                'MXN' => 'MXN - Peso Mexicain',
                            ])
                            ->default('EUR')
                            ->helperText('Détectée automatiquement par votre localisation IP'),
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
            ]);
    }
}
