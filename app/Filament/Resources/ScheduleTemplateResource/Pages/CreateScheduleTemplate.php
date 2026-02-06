<?php

namespace App\Filament\Resources\ScheduleTemplateResource\Pages;

use App\Filament\Resources\ScheduleTemplateResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduleTemplate extends CreateRecord
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;
        
        // Nettoyer les données de schedule_data (enlever les entrées vides)
        if (isset($data['schedule_data'])) {
            $data['schedule_data'] = array_filter($data['schedule_data'], function ($day) {
                return !empty($day['start_time']) && !empty($day['end_time']);
            });
        }

        return $data;
    }
}
