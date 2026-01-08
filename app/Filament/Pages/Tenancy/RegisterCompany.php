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
        // S'assurer que les permissions existent
        $this->ensurePermissionsExist();
        
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

    /**
     * S'assurer que toutes les permissions de base existent
     */
    protected function ensurePermissionsExist(): void
    {
        $permissions = [
            // Produits
            ['name' => 'Voir les produits', 'slug' => 'products.view', 'module' => 'products', 'action' => 'view'],
            ['name' => 'Créer des produits', 'slug' => 'products.create', 'module' => 'products', 'action' => 'create'],
            ['name' => 'Modifier des produits', 'slug' => 'products.edit', 'module' => 'products', 'action' => 'update'],
            ['name' => 'Supprimer des produits', 'slug' => 'products.delete', 'module' => 'products', 'action' => 'delete'],
            ['name' => 'Gérer le stock', 'slug' => 'products.stock', 'module' => 'products', 'action' => 'manage'],
            
            // Ventes
            ['name' => 'Voir les ventes', 'slug' => 'sales.view', 'module' => 'sales', 'action' => 'view'],
            ['name' => 'Créer des ventes', 'slug' => 'sales.create', 'module' => 'sales', 'action' => 'create'],
            ['name' => 'Modifier des ventes', 'slug' => 'sales.edit', 'module' => 'sales', 'action' => 'update'],
            ['name' => 'Supprimer des ventes', 'slug' => 'sales.delete', 'module' => 'sales', 'action' => 'delete'],
            
            // Achats
            ['name' => 'Voir les achats', 'slug' => 'purchases.view', 'module' => 'purchases', 'action' => 'view'],
            ['name' => 'Créer des achats', 'slug' => 'purchases.create', 'module' => 'purchases', 'action' => 'create'],
            ['name' => 'Modifier des achats', 'slug' => 'purchases.edit', 'module' => 'purchases', 'action' => 'update'],
            ['name' => 'Supprimer des achats', 'slug' => 'purchases.delete', 'module' => 'purchases', 'action' => 'delete'],
            
            // Clients
            ['name' => 'Voir les clients', 'slug' => 'customers.view', 'module' => 'customers', 'action' => 'view'],
            ['name' => 'Créer des clients', 'slug' => 'customers.create', 'module' => 'customers', 'action' => 'create'],
            ['name' => 'Modifier des clients', 'slug' => 'customers.edit', 'module' => 'customers', 'action' => 'update'],
            ['name' => 'Supprimer des clients', 'slug' => 'customers.delete', 'module' => 'customers', 'action' => 'delete'],
            
            // Fournisseurs
            ['name' => 'Voir les fournisseurs', 'slug' => 'suppliers.view', 'module' => 'suppliers', 'action' => 'view'],
            ['name' => 'Créer des fournisseurs', 'slug' => 'suppliers.create', 'module' => 'suppliers', 'action' => 'create'],
            ['name' => 'Modifier des fournisseurs', 'slug' => 'suppliers.edit', 'module' => 'suppliers', 'action' => 'update'],
            ['name' => 'Supprimer des fournisseurs', 'slug' => 'suppliers.delete', 'module' => 'suppliers', 'action' => 'delete'],
            
            // Devis
            ['name' => 'Voir les devis', 'slug' => 'quotes.view', 'module' => 'quotes', 'action' => 'view'],
            ['name' => 'Créer des devis', 'slug' => 'quotes.create', 'module' => 'quotes', 'action' => 'create'],
            ['name' => 'Modifier des devis', 'slug' => 'quotes.edit', 'module' => 'quotes', 'action' => 'update'],
            ['name' => 'Supprimer des devis', 'slug' => 'quotes.delete', 'module' => 'quotes', 'action' => 'delete'],
            
            // Bons de livraison
            ['name' => 'Voir les livraisons', 'slug' => 'deliveries.view', 'module' => 'deliveries', 'action' => 'view'],
            ['name' => 'Créer des livraisons', 'slug' => 'deliveries.create', 'module' => 'deliveries', 'action' => 'create'],
            ['name' => 'Modifier des livraisons', 'slug' => 'deliveries.edit', 'module' => 'deliveries', 'action' => 'update'],
            
            // Caisse (POS)
            ['name' => 'Accéder à la caisse', 'slug' => 'pos.access', 'module' => 'pos', 'action' => 'view'],
            ['name' => 'Ouvrir/fermer la caisse', 'slug' => 'pos.session', 'module' => 'pos', 'action' => 'manage'],
            ['name' => 'Voir les rapports caisse', 'slug' => 'pos.reports', 'module' => 'pos', 'action' => 'view'],
            
            // Entrepôts
            ['name' => 'Voir les entrepôts', 'slug' => 'warehouses.view', 'module' => 'warehouses', 'action' => 'view'],
            ['name' => 'Gérer les entrepôts', 'slug' => 'warehouses.manage', 'module' => 'warehouses', 'action' => 'manage'],
            
            // Transferts
            ['name' => 'Voir les transferts', 'slug' => 'transfers.view', 'module' => 'transfers', 'action' => 'view'],
            ['name' => 'Créer des transferts', 'slug' => 'transfers.create', 'module' => 'transfers', 'action' => 'create'],
            ['name' => 'Approuver des transferts', 'slug' => 'transfers.approve', 'module' => 'transfers', 'action' => 'update'],
            
            // Inventaires
            ['name' => 'Voir les inventaires', 'slug' => 'inventory.view', 'module' => 'inventory', 'action' => 'view'],
            ['name' => 'Gérer les inventaires', 'slug' => 'inventory.manage', 'module' => 'inventory', 'action' => 'manage'],
            
            // RH
            ['name' => 'Voir les employés', 'slug' => 'employees.view', 'module' => 'employees', 'action' => 'view'],
            ['name' => 'Créer des employés', 'slug' => 'employees.create', 'module' => 'employees', 'action' => 'create'],
            ['name' => 'Modifier des employés', 'slug' => 'employees.edit', 'module' => 'employees', 'action' => 'update'],
            ['name' => 'Supprimer des employés', 'slug' => 'employees.delete', 'module' => 'employees', 'action' => 'delete'],
            ['name' => 'Gérer le planning', 'slug' => 'schedule.manage', 'module' => 'hr', 'action' => 'manage'],
            ['name' => 'Gérer les congés', 'slug' => 'leaves.manage', 'module' => 'hr', 'action' => 'manage'],
            ['name' => 'Voir le pointage', 'slug' => 'attendance.view', 'module' => 'hr', 'action' => 'view'],
            ['name' => 'Gérer le pointage', 'slug' => 'attendance.manage', 'module' => 'hr', 'action' => 'manage'],
            
            // Comptabilité
            ['name' => 'Voir la comptabilité', 'slug' => 'accounting.view', 'module' => 'accounting', 'action' => 'view'],
            ['name' => 'Gérer la comptabilité', 'slug' => 'accounting.manage', 'module' => 'accounting', 'action' => 'manage'],
            
            // Banque
            ['name' => 'Voir les comptes bancaires', 'slug' => 'banking.view', 'module' => 'banking', 'action' => 'view'],
            ['name' => 'Gérer les comptes bancaires', 'slug' => 'banking.manage', 'module' => 'banking', 'action' => 'manage'],
            
            // Administration
            ['name' => 'Gérer les utilisateurs', 'slug' => 'users.manage', 'module' => 'admin', 'action' => 'manage'],
            ['name' => 'Gérer les rôles', 'slug' => 'roles.manage', 'module' => 'admin', 'action' => 'manage'],
            ['name' => 'Voir les rapports', 'slug' => 'reports.view', 'module' => 'admin', 'action' => 'view'],
            ['name' => 'Paramètres entreprise', 'slug' => 'settings.manage', 'module' => 'admin', 'action' => 'manage'],
        ];

        foreach ($permissions as $p) {
            \App\Models\Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }

    protected function getRedirectUrl(): string
    {
        $tenant = $this->tenant;

        // Forcer la redirection vers le dashboard du tenant
        return Filament::getPanel()->getUrl($tenant);
    }
}
