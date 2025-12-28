<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use Illuminate\Support\Str;

$companies = Company::all();
foreach ($companies as $company) {
    $company->slug = Str::slug($company->name);
    $company->save();
    echo "Updated company: " . $company->name . " with slug: " . $company->slug . "\n";
}
