<?php

namespace App\Providers\Filament;

use App\Filament\Pages\DefaultDashboard;
use App\Filament\Pages\Auth\Register;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\HtmlString;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

use Filament\Navigation\NavigationGroup;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(Register::class)
            ->tenant(\App\Models\Company::class, slugAttribute: 'slug')
            ->tenantRegistration(\App\Filament\Pages\Tenancy\RegisterCompany::class)
            ->tenantProfile(\App\Filament\Pages\Tenancy\EditCompanyProfile::class)
            ->brandName('FRECORP')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->darkMode(true) // Permet le toggle dark/light, dark par défaut
            ->colors([
                'primary' => [
                    50 => '#eef2ff',
                    100 => '#e0e7ff',
                    200 => '#c7d2fe',
                    300 => '#a5b4fc',
                    400 => '#818cf8',
                    500 => '#6366f1',
                    600 => '#4f46e5',
                    700 => '#4338ca',
                    800 => '#3730a3',
                    900 => '#312e81',
                    950 => '#1e1b4b',
                ],
                'gray' => [
                    50 => '#f8fafc',
                    100 => '#f1f5f9',
                    200 => '#e2e8f0',
                    300 => '#cbd5e1',
                    400 => '#94a3b8',
                    500 => '#64748b',
                    600 => '#475569',
                    700 => '#334155',
                    800 => '#1e293b',
                    900 => '#0f172a',
                    950 => '#020617',
                ],
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->font('Inter')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Ventes')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Stocks & Achats')
                    ->icon('heroicon-o-cube')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Point de Vente')
                    ->icon('heroicon-o-computer-desktop')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('RH')
                    ->icon('heroicon-o-users')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Comptabilité')
                    ->icon('heroicon-o-calculator')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Administration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->collapsed(),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('280px')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                DefaultDashboard::class,
                \App\Filament\Pages\Cashier\CashRegisterPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class, (Remplacé par notre widget personnalisé)
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(true)
                    ->timezone('Europe/Paris')
                    ->locale('fr'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\CheckSubscription::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->renderHook(
                PanelsRenderHook::BODY_START,
                function () {
                    $company = \Filament\Facades\Filament::getTenant();
                    if (!$company || !$company->isOnTrial()) {
                        return '';
                    }
                    $days = $company->trialDaysLeft();
                    if ($days > 14) {
                        return '';
                    }
                    $color = $days <= 3 ? 'bg-red-600' : 'bg-amber-500';
                    $msg   = $days <= 0
                        ? 'Votre période d\'essai gratuite se termine aujourd\'hui.'
                        : "Il vous reste <strong>{$days} jour" . ($days > 1 ? 's' : '') . "</strong> d'essai gratuit.";
                    return new \Illuminate\Support\HtmlString("
                        <div class='{$color} text-white text-center text-sm py-2 px-4 font-medium'>
                            {$msg}
                            &nbsp;·&nbsp;
                            <a href='mailto:contact@frecorp.fr?subject=Abonnement - {$company->name}' class='underline font-bold'>S'abonner maintenant (30€/mois)</a>
                        </div>
                    ");
                }
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                function () {
                    $company = \Filament\Facades\Filament::getTenant();
                    if (!$company || !$company->isPaymentFailing()) {
                        return '';
                    }
                    return new \Illuminate\Support\HtmlString("
                        <div class='bg-red-600 text-white text-center text-sm py-2 px-4 font-medium'>
                            <strong>Échec de paiement</strong> — Votre abonnement n'a pas pu être renouvelé.
                            &nbsp;·&nbsp;
                            <a href='https://billing.stripe.com' target='_blank' class='underline font-bold'>Mettre à jour mon moyen de paiement</a>
                        </div>
                    ");
                }
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => new HtmlString('
                    <script>
                        document.addEventListener("livewire:init", () => {
                            Livewire.on("get-current-location", () => {
                                if (navigator.geolocation) {
                                    // Essayer d\'abord avec haute précision
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            Livewire.dispatch("location-received", {
                                                latitude: position.coords.latitude,
                                                longitude: position.coords.longitude
                                            });
                                        },
                                        (error) => {
                                            // Si timeout avec haute précision, essayer sans
                                            if (error.code === error.TIMEOUT) {
                                                navigator.geolocation.getCurrentPosition(
                                                    (position) => {
                                                        Livewire.dispatch("location-received", {
                                                            latitude: position.coords.latitude,
                                                            longitude: position.coords.longitude
                                                        });
                                                    },
                                                    (error2) => {
                                                        handleGeoError(error2);
                                                    },
                                                    { enableHighAccuracy: false, timeout: 30000, maximumAge: 60000 }
                                                );
                                            } else {
                                                handleGeoError(error);
                                            }
                                        },
                                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                                    );
                                } else {
                                    Livewire.dispatch("location-error", { message: "Géolocalisation non supportée par ce navigateur" });
                                }
                            });

                            function handleGeoError(error) {
                                let message = "Erreur de géolocalisation";
                                switch(error.code) {
                                    case error.PERMISSION_DENIED:
                                        message = "Accès à la géolocalisation refusé. Veuillez autoriser l\'accès dans les paramètres du navigateur.";
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        message = "Position non disponible. Vérifiez que le GPS est activé.";
                                        break;
                                    case error.TIMEOUT:
                                        message = "Impossible d\'obtenir la position. Entrez les coordonnées manuellement.";
                                        break;
                                }
                                Livewire.dispatch("location-error", { message: message });
                            }
                        });
                    </script>
                ')
            );
    }
}
