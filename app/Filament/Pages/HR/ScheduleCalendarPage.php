<?php

namespace App\Filament\Pages\HR;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class ScheduleCalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Calendrier';

    protected static ?string $title = 'Calendrier des plannings';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.h-r.schedule-calendar-page';

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('hr') ?? false;
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('hr')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('schedule.view') || $user->hasPermission('schedule.manage');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ScheduleCalendar::class,
        ];
    }
}
