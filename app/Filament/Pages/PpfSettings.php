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
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.ppf-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Remplacé par FactPulseSettings — Chorus Pro est B2G uniquement
    }

    public static function canAccess(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $company = Filament::getTenant();
        $integration = $company->integrations()->where('service_name', 'ppf')->first();

        $settings = $integration?->settings ?? [];

        $this->form->fill([
            'is_active' => $integration?->is_active ?? false,
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
                    ]),

                Forms\Components\Section::make('Compte technique Chorus Pro')
                    ->description('Créez un compte technique sur chorus-pro.gouv.fr puis renseignez les informations ci-dessous')
                    ->schema([
                        Forms\Components\TextInput::make('fournisseur_siret')
                            ->label('SIRET de votre entreprise')
                            ->maxLength(14)
                            ->required()
                            ->helperText('Votre numéro SIRET à 14 chiffres')
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('fournisseur_login')
                            ->label('Login du compte technique')
                            ->placeholder('TECH_xxx@cpro.fr')
                            ->required()
                            ->helperText('Adresse email du compte technique créé sur Chorus Pro'),
                        Forms\Components\TextInput::make('fournisseur_password')
                            ->label('Mot de passe du compte technique')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('Mot de passe du compte technique'),
                    ])->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('is_active')),

                Forms\Components\Section::make('📖 Guide de configuration')
                    ->schema([
                        Forms\Components\Placeholder::make('guide')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-4 text-sm">
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <h4 class="font-semibold text-green-700 dark:text-green-300 mb-2">Comment créer un compte technique Chorus Pro ?</h4>
                                        <ol class="list-decimal list-inside space-y-1 text-green-600 dark:text-green-400">
                                            <li>Allez sur <a href="https://chorus-pro.gouv.fr/" target="_blank" class="underline">chorus-pro.gouv.fr</a></li>
                                            <li>Connectez-vous avec votre compte entreprise (créez-en un si nécessaire)</li>
                                            <li>Allez dans <strong>Paramètres > Comptes techniques</strong></li>
                                            <li>Cliquez sur <strong>"Créer un compte technique"</strong></li>
                                            <li>Sélectionnez votre structure</li>
                                            <li>Notez le <strong>login</strong> et <strong>mot de passe</strong> générés</li>
                                            <li>Revenez ici et saisissez ces informations</li>
                                        </ol>
                                    </div>
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-2">ℹ️ Information</h4>
                                        <p class="text-blue-600 dark:text-blue-400">
                                            La connexion à l\'API PISTE est gérée automatiquement par FRECORP ERP. 
                                            Vous n\'avez qu\'à créer votre compte technique Chorus Pro.
                                        </p>
                                    </div>
                                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <h4 class="font-semibold text-amber-700 dark:text-amber-300 mb-2">⚠️ Important</h4>
                                        <ul class="list-disc list-inside space-y-1 text-amber-600 dark:text-amber-400">
                                            <li>Les factures envoyées sont <strong>réelles et transmises à l\'État</strong></li>
                                            <li>Assurez-vous que votre <strong>SIRET</strong> et <strong>N° TVA</strong> sont corrects dans les paramètres de l\'entreprise</li>
                                            <li>Le compte technique doit être rattaché à la bonne structure dans Chorus Pro</li>
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
