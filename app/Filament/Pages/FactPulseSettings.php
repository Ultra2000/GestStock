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
        ]);
    }

    public function form(Form $form): Form
    {
        $configured = app(FactPulseService::class)->isConfigured();

        $statusHtml = $configured
            ? '<div class="flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                   <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center flex-shrink-0">
                       <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                   </div>
                   <div>
                       <p class="font-semibold text-emerald-700 dark:text-emerald-300">Facturation électronique activée</p>
                       <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-0.5">Vos factures sont prêtes à être envoyées automatiquement à vos clients.</p>
                   </div>
               </div>'
            : '<div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                   <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-800 flex items-center justify-center flex-shrink-0">
                       <svg class="w-5 h-5 text-amber-600 dark:text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                   </div>
                   <div>
                       <p class="font-semibold text-amber-700 dark:text-amber-300">Activation en cours de préparation</p>
                       <p class="text-sm text-amber-600 dark:text-amber-400 mt-0.5">La facturation électronique sera disponible prochainement sur votre compte. Aucune action n\'est requise de votre part.</p>
                   </div>
               </div>';

        return $form
            ->schema([
                Forms\Components\Section::make('État du service')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('')
                            ->content(new HtmlString($statusHtml)),
                    ]),

                Forms\Components\Section::make('Comment fonctionne l\'envoi à votre PDP ?')
                    ->description('PDP = Plateforme de Dématérialisation Partenaire, le tiers de confiance qui achemine vos factures.')
                    ->schema([
                        Forms\Components\Placeholder::make('howto')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="space-y-4 text-sm">

                                    <p class="text-gray-600 dark:text-gray-400">
                                        Depuis le 1er janvier 2026, la loi impose l\'envoi des factures entre entreprises
                                        par voie électronique via un réseau sécurisé certifié par l\'État.
                                        FRECORP s\'en charge automatiquement pour vous, sans manipulation technique.
                                    </p>

                                    <div class="flex gap-4 items-start">
                                        <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-sm font-bold">1</span>
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">Vous finalisez votre facture</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Créez et validez votre facture normalement dans FRECORP, comme vous le faites déjà.</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-4 items-start">
                                        <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-sm font-bold">2</span>
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">Vous cliquez sur « Envoyer »</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Un simple bouton sur la facture déclenche l\'envoi. FRECORP transmet automatiquement votre facture à notre PDP partenaire (FactPulse).</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-4 items-start">
                                        <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-sm font-bold">3</span>
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">FactPulse achemine la facture</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">FactPulse consulte l\'annuaire officiel de l\'État pour identifier le PDP utilisé par votre client, puis lui transmet la facture dans le bon format légal.</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-4 items-start">
                                        <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-sm font-bold">4</span>
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">Vous suivez l\'état en temps réel</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Le statut de chaque facture est mis à jour automatiquement : <em>Envoyée → Reçue → Acceptée</em>. Vous savez toujours où en est votre facture.</p>
                                        </div>
                                    </div>

                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 mt-2">
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            <strong>Seul prérequis :</strong> votre client doit avoir son numéro <strong>SIRET</strong> renseigné dans sa fiche. C\'est ce numéro qui permet d\'identifier son PDP dans l\'annuaire.
                                        </p>
                                    </div>

                                </div>
                            ')),
                    ]),

                Forms\Components\Section::make('Questions fréquentes')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Forms\Components\Placeholder::make('faq')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">Dois-je faire quelque chose pour activer ça ?</p>
                                        <p class="mt-1">Non, c\'est FRECORP qui gère tout. Dès que le service est actif, le bouton d\'envoi apparaît sur vos factures.</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">Mes factures aux particuliers sont-elles concernées ?</p>
                                        <p class="mt-1">Non. La facturation électronique obligatoire ne concerne que les transactions entre entreprises (B2B). Vos factures aux particuliers ne changent pas.</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">Que se passe-t-il si mon client n\'est pas encore inscrit à un PDP ?</p>
                                        <p class="mt-1">L\'État a prévu une période de transition. FactPulse gère automatiquement les cas particuliers. Vous serez notifié si une facture ne peut pas être transmise.</p>
                                    </div>
                                </div>
                            ')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [];
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
