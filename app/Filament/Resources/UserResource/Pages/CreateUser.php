<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Nouvel utilisateur';
    }

    protected function afterCreate(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return;
        }

        // Associer l'utilisateur à l'entreprise
        $this->record->companies()->syncWithoutDetaching([$tenant->id]);

        // Assigner les rôles sélectionnés
        $roleIds = $this->data['company_roles'] ?? [];
        
        if (!empty($roleIds)) {
            foreach ($roleIds as $roleId) {
                $this->record->roles()->attach($roleId, ['company_id' => $tenant->id]);
            }
        } else {
            // Assigner le rôle par défaut si aucun n'est sélectionné
            $defaultRole = Role::where('company_id', $tenant->id)
                ->where('is_default', true)
                ->first();
            
            if ($defaultRole) {
                $this->record->roles()->attach($defaultRole->id, ['company_id' => $tenant->id]);
            }
        }
    }
}
