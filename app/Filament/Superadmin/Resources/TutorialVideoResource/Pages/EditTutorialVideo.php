<?php

namespace App\Filament\Superadmin\Resources\TutorialVideoResource\Pages;

use App\Filament\Superadmin\Resources\TutorialVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTutorialVideo extends EditRecord
{
    protected static string $resource = TutorialVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
