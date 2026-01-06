<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    public function getTitle(): string
    {
        return 'Historique des modifications';
    }

    public function getHeading(): string
    {
        return 'Traçabilité et audit';
    }

    public function getSubheading(): ?string
    {
        return 'Consultez l\'historique complet de toutes les modifications effectuées sur vos données';
    }
}
