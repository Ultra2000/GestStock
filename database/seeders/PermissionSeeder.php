<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'products' => 'Produits',
            'customers' => 'Clients',
            'suppliers' => 'Fournisseurs',
            'sales' => 'Ventes',
            'purchases' => 'Achats',
            'users' => 'Utilisateurs',
            'roles' => 'Rôles',
            'reports' => 'Rapports',
            'settings' => 'Paramètres',
        ];

        foreach ($modules as $module => $moduleName) {
            $permissions = Permission::generateForModule($module, $moduleName);
            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['slug' => $permission['slug']],
                    $permission
                );
            }
        }

        $this->command->info('Permissions créées avec succès !');
    }
}
