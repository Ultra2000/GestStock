<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$companies = \App\Models\Company::all();

foreach ($companies as $company) {
    echo "=== Company: {$company->name} (ID: {$company->id}) ===" . PHP_EOL;
    echo "Settings: " . json_encode($company->settings, JSON_PRETTY_PRINT) . PHP_EOL;
    echo PHP_EOL;
    
    // Test des modules
    $modules = ['pos', 'stock', 'hr', 'accounting', 'banking'];
    echo "Module status:" . PHP_EOL;
    foreach ($modules as $module) {
        $status = $company->isModuleEnabled($module) ? '✅ Enabled' : '❌ Disabled';
        echo "  - {$module}: {$status}" . PHP_EOL;
    }
    echo PHP_EOL;
}
