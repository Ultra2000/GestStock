<?php

namespace App\Filament\Resources\InvitationResource\Pages;

use App\Filament\Resources\InvitationResource;
use App\Mail\InvitationMail;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;
        $data['invited_by'] = auth()->id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Envoyer l'email d'invitation
        try {
            Mail::to($this->record->email)->send(new InvitationMail($this->record));
            
            Notification::make()
                ->title('Invitation envoyée')
                ->body("Un email a été envoyé à {$this->record->email}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Invitation créée')
                ->body("L'invitation a été créée mais l'email n'a pas pu être envoyé. Vous pouvez copier le lien d'invitation.")
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
