<?php

namespace App\Filament\Superadmin\Resources\SubscriptionAuditResource\Pages;

use App\Filament\Superadmin\Resources\SubscriptionAuditResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionAudits extends ListRecords
{
    protected static string $resource = SubscriptionAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
