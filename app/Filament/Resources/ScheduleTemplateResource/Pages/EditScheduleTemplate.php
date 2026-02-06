<?php

namespace App\Filament\Resources\ScheduleTemplateResource\Pages;

use App\Filament\Resources\ScheduleTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheduleTemplate extends EditRecord
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Nettoyer les données de schedule_data (enlever les entrées vides)
        if (isset($data['schedule_data'])) {
            $data['schedule_data'] = array_filter($data['schedule_data'], function ($day) {
                return !empty($day['start_time']) && !empty($day['end_time']);
            });
        }

        return $data;
    }
}
