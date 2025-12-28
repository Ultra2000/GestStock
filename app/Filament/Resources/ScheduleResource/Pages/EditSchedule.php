<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Déterminer si c'est un horaire récurrent
        $data['is_recurring'] = empty($data['date']) && !empty($data['day_of_week']);
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si récurrent, effacer la date
        if (!empty($data['is_recurring'])) {
            $data['date'] = null;
        } else {
            $data['day_of_week'] = null;
        }
        
        unset($data['is_recurring']);
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
