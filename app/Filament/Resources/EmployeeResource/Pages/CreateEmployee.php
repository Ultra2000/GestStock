<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    // Temporary storage for user creation data
    public ?bool $shouldCreateUser = false;
    public ?string $userPassword = null;
    public ?int $userRoleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;
        
        // Extract user creation data
        if (isset($data['create_user']) && $data['create_user']) {
            $this->shouldCreateUser = true;
            $this->userPassword = $data['password'] ?? null;
            $this->userRoleId = $data['role_id'] ?? null;
        }

        // Clean up data before saving Employee
        unset($data['create_user']);
        unset($data['password']);
        unset($data['role_id']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Logic to create user and link it
        if ($this->shouldCreateUser && $this->record->email && $this->userPassword) {
            $employee = $this->record;
            
            // Check if user exists
            $user = User::where('email', $employee->email)->first();
            
            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'email' => $employee->email,
                    'password' => Hash::make($this->userPassword),
                    'is_active' => true,
                ]);
            } else {
                Notification::make()
                    ->warning()
                    ->title('Utilisateur existant')
                    ->body("Un utilisateur avec l'email {$user->email} existait déjà. L'employé a été lié à ce compte.")
                    ->send();
            }

            // Assign Company (Tenant)
            if (!$user->companies()->where('company_id', $employee->company_id)->exists()) {
                $user->companies()->attach($employee->company_id);
            }

            // Assign Role for this company
            if ($this->userRoleId) {
                $existingRole = $user->roles()
                    ->wherePivot('company_id', $employee->company_id)
                    ->where('id', $this->userRoleId)
                    ->exists();

                if (!$existingRole) {
                    $user->roles()->attach($this->userRoleId, ['company_id' => $employee->company_id]);
                }
            }

            // Link Employee to User
            $employee->user_id = $user->id;
            $employee->save();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
