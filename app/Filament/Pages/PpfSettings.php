<?php

namespace App\Filament\Pages;

use App\Models\CompanyIntegration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Services\Integration\PpfService;

class PpfSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Facturation électronique';
    protected static ?string $title = 'Facturation électronique (PPF / Chorus Pro)';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.ppf-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $company = Filament::getTenant();
        $integration = $company->integrations()->where('service_name', 'ppf')->first();

        $settings = $integration?->settings ?? [];

        $this->form->fill([
            'is_active' => $integration?->is_active ?? false,
            'environment' => $settings['environment'] ?? 'sandbox',
            'client_id' => $settings['client_id'] ?? '',
            'client_secret' => $settings['client_secret'] ?? '',
            'api_key' => $settings['api_key'] ?? '',
            'fournisseur_login' => $settings['fournisseur_login'] ?? '',
            'fournisseur_password' => $settings['fournisseur_password'] ?? '',
            'fournisseur_siret' => $settings['fournisseur_siret'] ?? $company->siret ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Activation')
                    ->description('Activez la facturation électronique pour envoyer vos factures vers Chorus Pro')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activer la facturation électronique')
                            ->helperText('Une fois activé, vous pourrez envoyer vos factures directement vers Chorus Pro')
                            ->live(),
                        Forms\Components\Select::make('environment')
                            ->label('Environnement')
                            ->options([
                                'sandbox' => '🧪 Sandbox (Test) - Pour tester sans impact',
                                'production' => '🚀 Production - Factures réelles',
                            ])
                            ->default('sandbox')
                            ->helperText('Commencez par le Sandbox pour tester, puis passez en Production'),
                    ]),

                Forms\Components\Section::make('1. Credentials PISTE (API)')
                    ->description('Ces informations se trouvent sur le portail PISTE : https://developer.aife.economie.gouv.fr/')
                    ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
                            ->helperText('Identifiant de votre application PISTE'),
                        Forms\Components\TextInput::make('client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Secret de votre application PISTE'),
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key (KeyId)')
                            ->password()
                            ->revealable()
                            ->helperText('Clé API pour l\'authentification'),
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => $get('is_active')),

                Forms\Components\Section::make('2. Compte technique Chorus Pro')
                    ->description('Ces informations se trouvent sur le portail Chorus Pro dans Paramètres > Comptes techniques')
                    ->schema([
                        Forms\Components\TextInput::make('fournisseur_login')
                            ->label('Login du compte technique')
                            ->placeholder('TECH_xxx@cpro.fr')
                            ->helperText('Adresse email du compte technique'),
                        Forms\Components\TextInput::make('fournisseur_password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable(),
                        Forms\Components\TextInput::make('fournisseur_siret')
                            ->label('SIRET de votre entreprise')
                            ->maxLength(14)
                            ->helperText('Votre numéro SIRET à 14 chiffres'),
                    ])->columns(3)
                    ->visible(fn (Forms\Get $get) => $get('is_active')),

                Forms\Components\Section::make('📖 Guide de configuration')
                    ->schema([
                        Forms\Components\Placeholder::make('guide')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-4 text-sm">
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-2">Étape 1 : Créer une application PISTE</h4>
                                        <ol class="list-decimal list-inside space-y-1 text-blue-600 dark:text-blue-400">
                                            <li>Allez sur <a href="https://developer.aife.economie.gouv.fr/" target="_blank" class="underline">developer.aife.economie.gouv.fr</a></li>
                                            <li>Créez un compte ou connectez-vous</li>
                                            <li>Créez une nouvelle application</li>
                                            <li>Souscrivez à l\'API "CPPro Factures"</li>
                                            <li>Copiez le Client ID, Client Secret et API Key</li>
                                        </ol>
                                    </div>
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <h4 class="font-semibold text-green-700 dark:text-green-300 mb-2">Étape 2 : Créer un compte technique Chorus Pro</h4>
                                        <ol class="list-decimal list-inside space-y-1 text-green-600 dark:text-green-400">
                                            <li>Allez sur <a href="https://chorus-pro.gouv.fr/" target="_blank" class="underline">chorus-pro.gouv.fr</a></li>
                                            <li>Connectez-vous avec votre compte entreprise</li>
                                            <li>Allez dans Paramètres > Comptes techniques</li>
                                            <li>Créez un compte technique pour votre structure</li>
                                            <li>Notez le login et mot de passe générés</li>
                                        </ol>
                                    </div>
                                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <h4 class="font-semibold text-amber-700 dark:text-amber-300 mb-2">⚠️ Important</h4>
                                        <ul class="list-disc list-inside space-y-1 text-amber-600 dark:text-amber-400">
                                            <li>Testez d\'abord en <strong>Sandbox</strong> avant de passer en Production</li>
                                            <li>Les factures envoyées en Production sont <strong>réelles et irrévocables</strong></li>
                                            <li>Assurez-vous que votre SIRET et N° TVA sont corrects dans les paramètres de l\'entreprise</li>
                                        </ul>
                                    </div>
                                </div>
                            ')),
                    ])->collapsible()
                    ->collapsed(fn (Forms\Get $get) => $get('is_active')),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $company = Filament::getTenant();

        $integration = CompanyIntegration::updateOrCreate(
            [
                'company_id' => $company->id,
                'service_name' => 'ppf',
            ],
            [
                'is_active' => $data['is_active'],
                'settings' => [
                    'environment' => $data['environment'],
                    'client_id' => $data['client_id'],
                    'client_secret' => $data['client_secret'],
                    'api_key' => $data['api_key'],
                    'fournisseur_login' => $data['fournisseur_login'],
                    'fournisseur_password' => $data['fournisseur_password'],
                    'fournisseur_siret' => $data['fournisseur_siret'],
                ],
            ]
        );

        Notification::make()
            ->title('Configuration sauvegardée')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $data = $this->form->getState();
        $company = Filament::getTenant();

        // Sauvegarder d'abord
        $this->save();

        $integration = $company->integrations()->where('service_name', 'ppf')->first();

        if (!$integration) {
            Notification::make()
                ->title('Configuration manquante')
                ->body('Veuillez d\'abord sauvegarder la configuration')
                ->danger()
                ->send();
            return;
        }

        try {
            $ppfService = app(PpfService::class);
            $success = $ppfService->authenticate($integration);

            if ($success) {
                Notification::make()
                    ->title('✅ Connexion réussie !')
                    ->body('Votre configuration est valide. Vous pouvez envoyer des factures.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('❌ Échec de connexion')
                    ->body($integration->last_error ?? 'Vérifiez vos credentials')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Sauvegarder')
                ->action('save')
                ->color('primary'),
            Action::make('test')
                ->label('Tester la connexion')
                ->action('testConnection')
                ->color('gray'),
        ];
    }
}
