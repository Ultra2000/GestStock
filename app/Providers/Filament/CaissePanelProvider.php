<?php

namespace App\Providers\Filament;

use App\Models\Company;
use Filament\PanelProvider;
use Filament\Panel;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Filament\Support\Colors\Color;
use App\Filament\Caisse\Pages\PointOfSale;
use App\Filament\Caisse\Pages\CashSessionPage;
use App\Filament\Caisse\Pages\SalesHistory;

class CaissePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('caisse')
            ->path('caisse')
            ->login()
            ->tenant(Company::class, slugAttribute: 'slug')
            ->colors([
                'primary' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info' => Color::Sky,
            ])
            ->font('Inter')
            ->darkMode(false)
            ->brandName('GestStock POS')
            ->pages([
                PointOfSale::class,
                CashSessionPage::class,
                SalesHistory::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->topNavigation();
    }
}
