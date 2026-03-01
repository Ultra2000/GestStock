<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Database\Seeders\RolesAndPermissionsSeeder;
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
                    ->regex('/^\d{9}$/')
                    ->validationMessages([
                        'regex' => 'Le SIREN doit contenir exactement 9 chiffres.',
                    ])
                    ->suffixAction(
                        Action::make('search_siren')
                            ->icon('heroicon-m-magnifying-glass')
                            ->label('Rechercher')
                            ->action(function ($state, Set $set) {
                                if (blank($state)) {
                                    Notification::make()->title('Veuillez entrer un numéro SIREN.')->warning()->send();
                                    return;
                                }

                                if (!preg_match('/^\d{9}$/', $state)) {
                                    Notification::make()->title('Le SIREN doit contenir exactement 9 chiffres.')->warning()->send();
                                    return;
                                }

                                try {
                                    $response = Http::timeout(5)->get("https://recherche-entreprises.api.gouv.fr/search", [
                                        'q' => $state,
                                        'limit' => 1
                                    ]);

                                    if ($response->successful() && count($response->json('results')) > 0) {
                                        $data = $response->json('results')[0];
                                        $siege = $data['siege'] ?? [];
                                        
                                        $set('name', $data['nom_complet'] ?? '');
                                        $set('address', $siege['adresse'] ?? '');
                                        $set('zip_code', $siege['code_postal'] ?? '');
                                        $set('city', $siege['libelle_commune'] ?? '');
                                        
                                        // SIRET du siège (SIREN + NIC)
                                        if (!empty($siege['siret'])) {
                                            $set('siret', $siege['siret']);
                                        }
                                        
                                        // Numéro de TVA intracommunautaire (calcul à partir du SIREN)
                                        // Formule : FR + clé + SIREN, clé = (12 + 3 × (SIREN mod 97)) mod 97
                                        $siren = $state;
                                        $cle = (12 + 3 * ((int) $siren % 97)) % 97;
                                        $tvaNumber = 'FR' . str_pad($cle, 2, '0', STR_PAD_LEFT) . $siren;
                                        $set('tax_number', $tvaNumber);
                                        
                                        // Vérification VIES (système européen) pour valider le numéro de TVA
                                        $viesStatus = null;
                                        try {
                                            $viesResponse = Http::timeout(8)->get('https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number', [
                                                'countryCode' => 'FR',
                                                'vatNumber' => substr($tvaNumber, 2), // Sans le préfixe FR
                                            ]);
                                            
                                            if ($viesResponse->successful()) {
                                                $viesData = $viesResponse->json();
                                                $viesStatus = $viesData['valid'] ?? false;
                                            }
                                        } catch (\Exception $e) {
                                            // VIES peut être indisponible — on ne bloque pas l'inscription
                                            $viesStatus = null;
                                        }
                                        
                                        if ($viesStatus === true) {
                                            Notification::make()
                                                ->title('Entreprise trouvée !')
                                                ->body("TVA : {$tvaNumber} — ✅ Vérifié VIES (actif)")
                                                ->success()
                                                ->duration(8000)
                                                ->send();
                                        } elseif ($viesStatus === false) {
                                            Notification::make()
                                                ->title('Entreprise trouvée !')
                                                ->body("TVA : {$tvaNumber}")
                                                ->success()
                                                ->send();
                                            Notification::make()
                                                ->title('⚠️ TVA non active dans VIES')
                                                ->body('Votre numéro de TVA n\'est pas encore reconnu comme actif pour les opérations intracommunautaires. Cela est courant pour les nouvelles entreprises. Contactez le SIE de votre centre des impôts si nécessaire.')
                                                ->warning()
                                                ->persistent()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('Entreprise trouvée !')
                                                ->body("TVA : {$tvaNumber} — Vérification VIES indisponible")
                                                ->success()
                                                ->send();
                                        }
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
                TextInput::make('siret')
                    ->label('SIRET')
                    ->placeholder('Rempli automatiquement')
                    ->maxLength(14)
                    ->helperText('Renseigné automatiquement via le SIREN.'),
                TextInput::make('tax_number')
                    ->label('N° TVA Intracommunautaire')
                    ->placeholder('Rempli automatiquement')
                    ->helperText('Calculé automatiquement à partir du SIREN.'),
                TextInput::make('address')
                    ->label('Adresse du siège'),
                TextInput::make('zip_code')
                    ->label('Code postal'),
                TextInput::make('city')
                    ->label('Ville'),
                TextInput::make('email')
                    ->label('Email de contact')
                    ->email(),
                TextInput::make('phone')
                    ->label('Téléphone'),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        // S'assurer que les permissions existent
        $this->ensurePermissionsExist();
        
        // Générer un slug unique
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        $company = Company::create($data);

        // Créer les rôles par défaut via la source unique de vérité
        $seeder = new RolesAndPermissionsSeeder();
        $seeder->createRolesForCompany($company);

        // Associer l'utilisateur à l'entreprise
        $company->users()->attach(auth()->user());

        // Assigner le rôle Admin à l'utilisateur créateur
        $adminRole = $company->roles()->where('slug', 'admin')->first();
        if ($adminRole) {
            auth()->user()->assignRole($adminRole, $company);
        }

        return $company;
    }

    /**
     * Génère un slug unique pour éviter les collisions
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . ++$counter;
        }

        return $slug;
    }

    /**
     * S'assurer que toutes les permissions de base existent.
     * Délègue au seeder pour avoir une source unique de vérité.
     */
    protected function ensurePermissionsExist(): void
    {
        $seeder = new RolesAndPermissionsSeeder();
        $seeder->ensurePermissionsExist();
    }

    protected function getRedirectUrl(): string
    {
        $tenant = $this->tenant;

        // Forcer la redirection vers le dashboard du tenant
        return Filament::getPanel()->getUrl($tenant);
    }
}
