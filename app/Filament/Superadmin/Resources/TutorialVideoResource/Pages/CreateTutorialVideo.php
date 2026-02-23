<?php

namespace App\Filament\Superadmin\Resources\TutorialVideoResource\Pages;

use App\Filament\Superadmin\Resources\TutorialVideoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTutorialVideo extends CreateRecord
{
    protected static string $resource = TutorialVideoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
