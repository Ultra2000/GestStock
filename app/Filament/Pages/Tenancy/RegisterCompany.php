<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Enregistrer une entreprise';
    }

    public static function canView(): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('registration_number')
                    ->label('Numéro SIREN')
                    ->placeholder('Ex: 123456789')
                    ->helperText('Saisissez votre SIREN pour remplir automatiquement les informations.')
                    ->suffixAction(
                        Action::make('search_siren')
                            ->icon('heroicon-m-magnifying-glass')
                            ->label('Rechercher')
                            ->action(function ($state, Set $set) {
                                if (blank($state)) {
                                    Notification::make()->title('Veuillez entrer un numéro SIREN.')->warning()->send();
                                    return;
                                }

                                try {
                                    $response = Http::timeout(5)->get("https://recherche-entreprises.api.gouv.fr/search", [
                                        'q' => $state,
                                        'limit' => 1
                                    ]);

                                    if ($response->successful() && count($response->json('results')) > 0) {
                                        $data = $response->json('results')[0];
                                        
                                        $set('name', $data['nom_complet']);
                                        $set('address', $data['siege']['adresse']);
                                        
                                        Notification::make()->title('Entreprise trouvée !')->success()->send();
                                    } else {
                                        Notification::make()->title('Aucune entreprise trouvée pour ce SIREN.')->warning()->send();
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()->title('Erreur de connexion à l\'API.')->danger()->send();
                                }
                            })
                    ),
                TextInput::make('name')
                    ->label('Nom de l\'entreprise')
                    ->required(),
                TextInput::make('address')
                    ->label('Adresse du siège'),
                TextInput::make('email')
                    ->label('Email de contact')
                    ->email(),
                TextInput::make('phone')
                    ->label('Téléphone'),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        // Générer le slug avant création
        $data['slug'] = Str::slug($data['name']);

        $company = Company::create($data);

        // Créer les rôles par défaut pour cette entreprise
        $roleSeeder = new RoleSeeder();
        $roleSeeder->createRolesForCompany($company);

        // Associer l'utilisateur à l'entreprise
        $company->users()->attach(auth()->user());

        // Assigner le rôle Admin à l'utilisateur créateur
        $adminRole = $company->roles()->where('slug', 'admin')->first();
        if ($adminRole) {
            auth()->user()->assignRole($adminRole, $company);
        }

        return $company;
    }

    protected function getRedirectUrl(): string
    {
        $tenant = $this->tenant;

        // Forcer la redirection vers le dashboard du tenant
        return Filament::getPanel()->getUrl($tenant);
    }
}
