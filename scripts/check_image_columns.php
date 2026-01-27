<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'models.image: ' . (\Illuminate\Support\Facades\Schema::hasColumn('models', 'image') ? 'yes' : 'no') . PHP_EOL;
echo 'brands.image: ' . (\Illuminate\Support\Facades\Schema::hasColumn('brands', 'image') ? 'yes' : 'no') . PHP_EOL;
