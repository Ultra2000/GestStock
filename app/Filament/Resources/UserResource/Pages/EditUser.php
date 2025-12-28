<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer'),
        ];
    }

    public function getTitle(): string
    {
        return 'Modifier l\'utilisateur';
    }

    protected function afterSave(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return;
        }

        // Synchroniser les rôles pour cette entreprise
        $roleIds = $this->data['company_roles'] ?? [];
        
        // Retirer tous les rôles existants pour cette entreprise
        $this->record->roles()->wherePivot('company_id', $tenant->id)->detach();
        
        // Ajouter les nouveaux rôles
        foreach ($roleIds as $roleId) {
            $this->record->roles()->attach($roleId, ['company_id' => $tenant->id]);
        }
    }
}
