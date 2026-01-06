<?php

namespace App\Filament\Resources\AccountingSettingResource\Pages;

use App\Filament\Resources\AccountingSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAccountingSettings extends ManageRecords
{
    protected static string $resource = AccountingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Configurer')
                ->icon('heroicon-o-cog-6-tooth')
                ->visible(fn () => !$this->getTableRecords()->count()),
        ];
    }

    public function getTitle(): string
    {
        return 'Paramètres Comptables';
    }

    public function getHeading(): string
    {
        return 'Configuration Comptable';
    }

    public function getSubheading(): ?string
    {
        return 'Configurez les numéros de comptes et journaux selon le Plan Comptable Général';
    }
}
