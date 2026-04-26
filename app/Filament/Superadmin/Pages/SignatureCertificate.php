<?php

namespace App\Filament\Superadmin\Pages;

use App\Services\PdfSignatureService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SignatureCertificate extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Signature PDF';
    protected static ?string $navigationGroup = 'Système';
    protected static ?int    $sort            = 30;
    protected static string  $view            = 'filament.superadmin.pages.signature-certificate';

    public ?string $certName    = '';
    public ?string $certCountry = 'FR';
    public ?string $certEmail   = '';

    public function mount(): void
    {
        $this->certName = config('app.name', 'FRECORP ERP');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('certName')
                        ->label('Nom / Organisation')
                        ->required(),
                    TextInput::make('certCountry')
                        ->label('Code pays')
                        ->maxLength(2)
                        ->required(),
                    TextInput::make('certEmail')
                        ->label('Email (optionnel)')
                        ->email(),
                ]),
            ])
            ->statePath('');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Générer un certificat auto-signé')
                ->icon('heroicon-o-key')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Générer un nouveau certificat')
                ->modalDescription('Cette action remplacera tout certificat existant. Les PDFs signés précédemment restent valides.')
                ->action(function () {
                    $service = new PdfSignatureService();
                    $ok = $service->generateSelfSignedCertificate([
                        'name'    => $this->certName ?: config('app.name'),
                        'country' => $this->certCountry ?: 'FR',
                        'email'   => $this->certEmail ?: '',
                    ]);

                    if ($ok) {
                        Notification::make()
                            ->title('Certificat généré avec succès')
                            ->body('Valable 10 ans. Activez la signature dans les réglages de chaque entreprise.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Erreur lors de la génération')
                            ->body('Vérifiez que l\'extension OpenSSL PHP est activée.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getCertificateInfo(): ?array
    {
        return (new PdfSignatureService())->getCertificateInfo();
    }
}
