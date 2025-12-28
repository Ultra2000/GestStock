<?php

namespace App\Filament\Resources\CommissionResource\Pages;

use App\Filament\Resources\CommissionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCommission extends CreateRecord
{
    protected static string $resource = CommissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;
        
        return $data;
    }
}
