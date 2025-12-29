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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                'Ventes',
                'Stocks & Achats',
                'Point de Vente',
                'RH',
                'Comptabilité',
                'Administration',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                DefaultDashboard::class,
                \App\Filament\Pages\Cashier\CashRegisterPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                \App\Http\Middleware\RedirectToTenant::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
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
