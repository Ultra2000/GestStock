<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Services\FactPulseService;
use Illuminate\Support\HtmlString;

class FactPulseSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Facturation électronique';
    protected static ?string $title = 'Facturation électronique — FactPulse';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.factpulse-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->isAdmin();
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'configured' => app(FactPulseService::class)->isConfigured(),
            'api_url'    => config('services.factpulse.api_url'),
        ]);
    }

    public function form(Form $form): Form
    {
        $configured = app(FactPulseService::class)->isConfigured();

        return $form
            ->schema([
                Forms\Components\Section::make('État de la configuration')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('')
                            ->content(new HtmlString(
                                $configured
                                    ? '<div class="flex items-center gap-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                           <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                           FactPulse est configuré et prêt à l\'emploi.
                                       </div>'
                                    : '<div class="flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-400">
                                           <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                           FactPulse n\'est pas encore configuré. Ajoutez les variables ci-dessous dans votre fichier <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">.env</code>.
                                       </div>'
                            )),
                    ]),

                Forms\Components\Section::make('Variables .env à configurer sur le serveur')
                    ->description('Ces valeurs sont fournies par FactPulse lors de votre inscription.')
                    ->schema([
                        Forms\Components\Placeholder::make('env_block')
                            ->label('')
                            ->content(new HtmlString('
                                <pre class="text-xs bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto leading-relaxed">
FACTPULSE_API_URL=https://api.factpulse.fr/v1
FACTPULSE_EMAIL=votre@email.com
FACTPULSE_PASSWORD=votre_mot_de_passe
FACTPULSE_CLIENT_UID=votre_client_uid
                                </pre>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Après modification du .env, exécutez <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan config:clear</code> sur le serveur.
                                </p>
                            ')),
                    ]),

                Forms\Components\Section::make('Comment ça fonctionne ?')
                    ->schema([
                        Forms\Components\Placeholder::make('howto')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="space-y-3 text-sm">
                                    <div class="flex gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                        <span>Sur une facture <strong>Terminée</strong>, cliquez sur <strong>"Dématérialiser (FactPulse)"</strong></span>
                                    </div>
                                    <div class="flex gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                                        <span>FRECORP envoie les données à FactPulse (numéro, montants, SIRET émetteur + destinataire)</span>
                                    </div>
                                    <div class="flex gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                                        <span>FactPulse génère le <strong>Factur-X</strong> et route automatiquement vers le <strong>PDP du destinataire</strong> via l\'annuaire officiel</span>
                                    </div>
                                    <div class="flex gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-xs font-bold">4</span>
                                        <span>Le statut (<em>soumise → transmise</em>) est affiché dans la liste des factures</span>
                                    </div>
                                    <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300">
                                        <strong>Prérequis :</strong> le client destinataire doit avoir un <strong>SIRET</strong> renseigné dans sa fiche.
                                    </div>
                                </div>
                            ')),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Tester la connexion')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action('testConnection'),
        ];
    }

    public function testConnection(): void
    {
        $service = app(FactPulseService::class);

        if (!$service->isConfigured()) {
            Notification::make()
                ->title('FactPulse non configuré')
                ->body('Ajoutez les variables FACTPULSE_* dans votre .env et relancez php artisan config:clear.')
                ->warning()
                ->send();
            return;
        }

        // Ping simple sur l'URL de base pour vérifier la disponibilité
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withHeaders([
                    'X-FactPulse-Email'      => config('services.factpulse.email'),
                    'X-FactPulse-Password'   => config('services.factpulse.password'),
                    'X-FactPulse-Client-UID' => config('services.factpulse.client_uid'),
                ])
                ->get(config('services.factpulse.api_url') . '/ping');

            if ($response->successful()) {
                Notification::make()
                    ->title('Connexion FactPulse réussie')
                    ->body('API accessible et credentials valides.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Erreur ' . $response->status())
                    ->body($response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Impossible de joindre FactPulse')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
