<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::with(['companies', 'roles'])->get();

echo "=== LISTE DES UTILISATEURS ET LEURS DROITS ===" . PHP_EOL . PHP_EOL;

foreach ($users as $user) {
    echo "👤 User: {$user->name} (ID: {$user->id}, Email: {$user->email})" . PHP_EOL;
    echo "   Super Admin: " . ($user->is_super_admin ? '✅ OUI' : '❌ NON') . PHP_EOL;
    
    // Companies
    echo "   Companies: ";
    if ($user->companies->count() > 0) {
        $companyList = [];
        foreach ($user->companies as $company) {
            $companyList[] = $company->name . " (ID: {$company->id})";
        }
        echo implode(', ', $companyList);
    } else {
        echo "Aucune";
    }
    echo PHP_EOL;
    
    // Roles for each company
    foreach ($user->companies as $company) {
        echo "   Rôles pour {$company->name}:" . PHP_EOL;
        $roles = $user->rolesForCompany($company);
        if ($roles->count() > 0) {
            foreach ($roles as $role) {
                $permissions = $role->permissions ? $role->permissions->pluck('slug')->join(', ') : 'aucune';
                echo "      - {$role->name} (slug: {$role->slug})" . PHP_EOL;
                echo "        Permissions: {$permissions}" . PHP_EOL;
            }
        } else {
            echo "      ❌ Aucun rôle assigné!" . PHP_EOL;
        }
    }
    
    echo PHP_EOL;
}

echo PHP_EOL . "=== RÔLES EXISTANTS PAR ENTREPRISE ===" . PHP_EOL;
$companies = \App\Models\Company::with('roles.permissions')->get();
foreach ($companies as $company) {
    echo PHP_EOL . "📦 Company: {$company->name} (ID: {$company->id})" . PHP_EOL;
    foreach ($company->roles as $role) {
        $permissions = $role->permissions->pluck('slug')->join(', ');
        echo "   - {$role->name} (slug: {$role->slug})" . PHP_EOL;
        if ($permissions) {
            echo "     Permissions: {$permissions}" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "=== TABLE model_has_roles ===" . PHP_EOL;
$modelHasRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')->get();
foreach ($modelHasRoles as $row) {
    $user = \App\Models\User::find($row->user_id);
    $role = \App\Models\Role::find($row->role_id);
    $company = \App\Models\Company::find($row->company_id);
    echo "  User: " . ($user ? $user->name : 'N/A') . " -> Role: " . ($role ? $role->name : 'N/A') . " -> Company: " . ($company ? $company->name : 'N/A') . PHP_EOL;
}
