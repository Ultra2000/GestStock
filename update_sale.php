<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$s = App\Models\Sale::first();
$s->ppf_status = 'DEPOSEE';
$s->ppf_id = 'CPP0011106000000000008749';
$s->save();

echo "Updated: {$s->ppf_status} | {$s->ppf_id}\n";
