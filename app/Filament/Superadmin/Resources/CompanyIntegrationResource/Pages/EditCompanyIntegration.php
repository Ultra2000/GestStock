<?php

namespace App\Filament\Superadmin\Resources\CompanyIntegrationResource\Pages;

use App\Filament\Superadmin\Resources\CompanyIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyIntegration extends EditRecord
{
    protected static string $resource = CompanyIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
