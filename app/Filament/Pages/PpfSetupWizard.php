<?php

namespace App\Filament\Pages;

use App\Models\CompanyIntegration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use App\Services\Integration\PpfService;
use Illuminate\Support\HtmlString;

class PpfSetupWizard extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Assistant facturation';
    protected static ?string $title = 'Assistant de configuration - Facturation électronique';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        // Masquer l'assistant si déjà configuré
        $company = \Filament\Facades\Filament::getTenant();
        if (!$company) return false;
        
        $integration = $company->integrations()->where('service_name', 'ppf')->first();
        return !($integration && $integration->is_active && !empty($integration->settings['fournisseur_login']));
    }

    protected static string $view = 'filament.pages.ppf-setup-wizard';

    public int $currentStep = 1;
    public ?array $data = [];
    public bool $testPassed = false;
    public ?string $testError = null;

    public function mount(): void
    {
        $company = Filament::getTenant();
        $integration = $company->integrations()->where('service_name', 'ppf')->first();

        // Si déjà configuré, rediriger vers les paramètres
        if ($integration && $integration->is_active && !empty($integration->settings['fournisseur_login'])) {
            $this->redirect(PpfSettings::getUrl());
            return;
        }

        $settings = $integration?->settings ?? [];
        $siret = $settings['fournisseur_siret'] ?? $company->siret ?? '';
        $login = $settings['fournisseur_login'] ?? '';
        $password = $settings['fournisseur_password'] ?? '';

        $this->form->fill([
            'fournisseur_siret' => $siret,
            'fournisseur_login' => $login,
            'fournisseur_password' => $password,
            'fournisseur_siret_input' => $siret,
            'fournisseur_login_input' => $login,
            'fournisseur_password_input' => $password,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Step 1: Introduction
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('intro')
                            ->content(new HtmlString('
                                <div class="text-center py-8">
                                    <div class="inline-flex items-center justify-center w-20 h-20 bg-primary-100 dark:bg-primary-900/50 rounded-full mb-6">
                                        <x-heroicon-o-paper-airplane class="w-10 h-10 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Bienvenue dans l\'assistant de facturation électronique
                                    </h2>
                                    <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto mb-6">
                                        À partir de 2026, toutes les entreprises françaises devront envoyer leurs factures 
                                        via le Portail Public de Facturation (Chorus Pro).
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto text-left">
                                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                            <div class="font-semibold text-green-700 dark:text-green-300 mb-1">✅ Conforme</div>
                                            <div class="text-sm text-green-600 dark:text-green-400">Respectez la réglementation française</div>
                                        </div>
                                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <div class="font-semibold text-blue-700 dark:text-blue-300 mb-1">⚡ Automatique</div>
                                            <div class="text-sm text-blue-600 dark:text-blue-400">Envoi direct depuis GestStock</div>
                                        </div>
                                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                            <div class="font-semibold text-purple-700 dark:text-purple-300 mb-1">📊 Suivi</div>
                                            <div class="text-sm text-purple-600 dark:text-purple-400">Statut de vos factures en temps réel</div>
                                        </div>
                                    </div>
                                </div>
                            ')),
                    ])
                    ->visible(fn () => $this->currentStep === 1),

                // Step 2: Créer compte Chorus Pro
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('chorus_guide')
                            ->content(new HtmlString('
                                <div class="space-y-6">
                                    <div class="text-center mb-8">
                                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                            Étape 1 : Créer votre compte technique Chorus Pro
                                        </h2>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            Suivez les instructions ci-dessous pour créer votre compte technique
                                        </p>
                                    </div>

                                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <div class="p-6 space-y-6">
                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">Accédez à Chorus Pro</h4>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                                        Cliquez sur le bouton ci-dessous pour ouvrir le portail Chorus Pro dans un nouvel onglet.
                                                    </p>
                                                    <a href="https://chorus-pro.gouv.fr" target="_blank" 
                                                       class="inline-flex items-center gap-2 mt-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                                                        <span>Ouvrir Chorus Pro</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">Connectez-vous</h4>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                                        Utilisez votre compte existant ou créez-en un nouveau avec votre SIRET.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">Allez dans les paramètres</h4>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                                        Menu → <strong>Paramètres</strong> → <strong>Comptes techniques</strong>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold">4</div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">Créez le compte technique</h4>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                                        Cliquez sur <strong>"Créer un compte technique"</strong>, sélectionnez votre structure, puis validez.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">5</div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">Notez vos identifiants</h4>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                                        <strong class="text-amber-600 dark:text-amber-400">Important !</strong> Copiez le <strong>login</strong> (format: TECH_xxx@cpro.fr) 
                                                        et le <strong>mot de passe</strong> générés. Vous en aurez besoin à l\'étape suivante.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                        <div class="flex gap-3">
                                            <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="text-sm text-amber-700 dark:text-amber-300">
                                                <strong>Note :</strong> Si vous avez déjà un compte technique, vous pouvez passer directement à l\'étape suivante.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ')),
                    ])
                    ->visible(fn () => $this->currentStep === 2),

                // Champs de credentials (toujours dans le state, mais affichés conditionnellement)
                Forms\Components\Hidden::make('fournisseur_siret')
                    ->default(''),
                Forms\Components\Hidden::make('fournisseur_login')
                    ->default(''),
                Forms\Components\Hidden::make('fournisseur_password')
                    ->default(''),

                // Step 3: Saisir les identifiants
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('credentials_intro')
                            ->content(new HtmlString('
                                <div class="text-center mb-8">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                        Étape 2 : Renseignez vos identifiants
                                    </h2>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Entrez les informations de votre compte technique Chorus Pro
                                    </p>
                                </div>
                            ')),

                        Forms\Components\TextInput::make('fournisseur_siret_input')
                            ->label('SIRET de votre entreprise')
                            ->required()
                            ->maxLength(14)
                            ->minLength(14)
                            ->numeric()
                            ->placeholder('12345678901234')
                            ->helperText('Votre numéro SIRET à 14 chiffres')
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('fournisseur_siret', $state))
                            ->live(),

                        Forms\Components\TextInput::make('fournisseur_login_input')
                            ->label('Login du compte technique')
                            ->required()
                            ->placeholder('TECH_xxx@cpro.fr')
                            ->helperText('Le login généré par Chorus Pro (format: TECH_xxx@cpro.fr)')
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('fournisseur_login', $state))
                            ->live(),

                        Forms\Components\TextInput::make('fournisseur_password_input')
                            ->label('Mot de passe du compte technique')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Le mot de passe généré par Chorus Pro')
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('fournisseur_password', $state))
                            ->live(),
                    ])
                    ->visible(fn () => $this->currentStep === 3),

                // Step 4: Test et confirmation
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('test_intro')
                            ->content(new HtmlString('
                                <div class="text-center mb-8">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                        Étape 3 : Vérification
                                    </h2>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Testons la connexion pour vérifier que tout fonctionne
                                    </p>
                                </div>
                            ')),

                        Forms\Components\Placeholder::make('test_result')
                            ->content(fn () => new HtmlString($this->getTestResultHtml())),
                    ])
                    ->visible(fn () => $this->currentStep === 4),

                // Step 5: Terminé
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('success')
                            ->content(new HtmlString('
                                <div class="text-center py-8">
                                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 dark:bg-green-900/50 rounded-full mb-6">
                                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        🎉 Configuration terminée !
                                    </h2>
                                    <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto mb-8">
                                        Votre entreprise est maintenant prête à envoyer des factures électroniques via Chorus Pro.
                                    </p>
                                    
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-6 max-w-lg mx-auto mb-8">
                                        <h3 class="font-semibold text-green-800 dark:text-green-200 mb-3">Prochaines étapes :</h3>
                                        <ul class="text-left text-sm text-green-700 dark:text-green-300 space-y-2">
                                            <li class="flex items-start gap-2">
                                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Créez une vente dans GestStock</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Cliquez sur "Envoyer au PPF" sur la facture</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Suivez le statut dans la liste des ventes</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            ')),
                    ])
                    ->visible(fn () => $this->currentStep === 5),
            ])
            ->statePath('data');
    }

    protected function getTestResultHtml(): string
    {
        if ($this->testPassed) {
            return '
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-full mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-green-700 dark:text-green-300 mb-2">Connexion réussie !</h3>
                    <p class="text-green-600 dark:text-green-400">Vos identifiants sont valides. Cliquez sur "Terminer" pour finaliser la configuration.</p>
                </div>
            ';
        }

        if ($this->testError) {
            return '
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-red-700 dark:text-red-300 mb-2">Échec de la connexion</h3>
                    <p class="text-red-600 dark:text-red-400 mb-4">' . e($this->testError) . '</p>
                    <p class="text-sm text-gray-500">Vérifiez vos identifiants et réessayez, ou retournez à l\'étape précédente.</p>
                </div>
            ';
        }

        return '
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Prêt à tester</h3>
                <p class="text-gray-600 dark:text-gray-400">Cliquez sur "Tester la connexion" pour vérifier vos identifiants.</p>
            </div>
        ';
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 3) {
            $this->form->validate();
        }

        if ($this->currentStep < 5) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->testPassed = false;
            $this->testError = null;
        }
    }

    public function testConnection(): void
    {
        $this->form->validate();
        $data = $this->form->getState();
        $company = Filament::getTenant();

        // Vérifier que les champs requis sont présents
        $login = $data['fournisseur_login'] ?? null;
        $password = $data['fournisseur_password'] ?? null;
        $siret = $data['fournisseur_siret'] ?? null;

        if (empty($login) || empty($password) || empty($siret)) {
            $this->testPassed = false;
            $this->testError = 'Veuillez remplir tous les champs (SIRET, login et mot de passe)';
            return;
        }

        // Sauvegarder temporairement
        $integration = CompanyIntegration::updateOrCreate(
            [
                'company_id' => $company->id,
                'service_name' => 'ppf',
            ],
            [
                'is_active' => false, // Pas encore actif
                'settings' => [
                    'fournisseur_login' => $login,
                    'fournisseur_password' => $password,
                    'fournisseur_siret' => $siret,
                ],
            ]
        );

        try {
            $ppfService = app(PpfService::class);
            $success = $ppfService->authenticate($integration);

            if ($success) {
                $this->testPassed = true;
                $this->testError = null;
                
                Notification::make()
                    ->title('Connexion réussie !')
                    ->success()
                    ->send();
            } else {
                $this->testPassed = false;
                $this->testError = $integration->last_error ?? 'Vérifiez vos identifiants Chorus Pro';
            }
        } catch (\Exception $e) {
            $this->testPassed = false;
            $this->testError = $e->getMessage();
        }
    }

    public function finish(): void
    {
        if (!$this->testPassed) {
            Notification::make()
                ->title('Veuillez d\'abord tester la connexion')
                ->warning()
                ->send();
            return;
        }

        $company = Filament::getTenant();
        $integration = $company->integrations()->where('service_name', 'ppf')->first();

        if ($integration) {
            $integration->update(['is_active' => true]);
        }

        $this->currentStep = 5;

        Notification::make()
            ->title('Configuration terminée !')
            ->body('Vous pouvez maintenant envoyer des factures électroniques.')
            ->success()
            ->send();
    }

    public function goToSales(): void
    {
        $this->redirect(route('filament.admin.resources.sales.index', ['tenant' => Filament::getTenant()->slug]));
    }
}
