<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau créneau'),
            Actions\Action::make('planning_view')
                ->label('Vue Planning')
                ->icon('heroicon-o-calendar-days')
                ->url(fn () => \App\Filament\Pages\SchedulePlanning::getUrl())
                ->openUrlInNewTab(false),
        ];
    }
}
