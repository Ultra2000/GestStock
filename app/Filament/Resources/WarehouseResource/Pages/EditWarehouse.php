<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    #[On('location-received')]
    public function setLocation(float $latitude, float $longitude): void
    {
        $this->form->fill([
            ...$this->form->getState(),
            'latitude' => round($latitude, 8),
            'longitude' => round($longitude, 8),
        ]);

        Notification::make()
            ->title('Position récupérée')
            ->body("Latitude: {$latitude}, Longitude: {$longitude}")
            ->success()
            ->send();
    }

    #[On('location-error')]
    public function handleLocationError(string $message): void
    {
        Notification::make()
            ->title('Erreur de géolocalisation')
            ->body($message)
            ->danger()
            ->send();
    }
}
