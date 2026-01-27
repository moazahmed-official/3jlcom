<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $affected = \Illuminate\Support\Facades\DB::table('models')->where('id', 1)->update(['image' => 'models/test.png']);
    echo 'update affected rows: ' . $affected . PHP_EOL;
} catch (Throwable $e) {
    echo 'error: ' . $e->getMessage() . PHP_EOL;
}
