<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::where('email', 'admin@example.com')->first();
    if (!$user) {
        echo "NO_USER\n";
        exit(1);
    }
    $tokenResult = $user->createToken('cli-test-token');
    echo $tokenResult->plainTextToken . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
