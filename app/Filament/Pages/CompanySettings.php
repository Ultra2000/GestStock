<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CompanySettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.pages.company-settings';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Informations de l\'entreprise';
    protected static ?string $title = 'Informations de l\'entreprise';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];
    public $name;
    public $address;
    public $phone;
    public $email;
    public $website;
    public $tax_number;
    public $registration_number;
    public $logo_path;
    public $footer_text;

    public function mount(): void
    {
        $company = CompanySetting::getCompany();
        $this->form->fill($company->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de l\'entreprise')
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('website')
                            ->label('Site web')
                            ->url(),
                    ])->columns(2),

                Forms\Components\Section::make('Informations légales')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Numéro de taxe'),
                        Forms\Components\TextInput::make('registration_number')
                            ->label('Numéro d\'enregistrement'),
                    ])->columns(2),

                Forms\Components\Section::make('Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo de l\'entreprise')
                            ->image()
                            ->directory('company')
                            ->visibility('public'),
                    ]),

                Forms\Components\Section::make('Texte de pied de page')
                    ->schema([
                        Forms\Components\Textarea::make('footer_text')
                            ->label('Texte à afficher en bas des factures')
                            ->rows(3),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $company = CompanySetting::getCompany();
        $company->fill($data);
        $company->save();

        Notification::make()
            ->title('Paramètres enregistrés avec succès')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
} 