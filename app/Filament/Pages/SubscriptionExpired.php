<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;

class SubscriptionExpired extends Page
{
    protected static ?string $navigationIcon = null;
    protected static string  $view           = 'filament.pages.subscription-expired';
    protected static bool    $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return true;
    }

    public function getCompany()
    {
        return Filament::getTenant();
    }
}
