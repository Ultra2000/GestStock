<?php

namespace App\Filament\Superadmin\Resources\TutorialVideoResource\Pages;

use App\Filament\Superadmin\Resources\TutorialVideoResource;
use Filament\Resources\Pages\ListRecords;

class ListTutorialVideos extends ListRecords
{
    protected static string $resource = TutorialVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
