<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::updateOrCreate([
        'email' => 'admin@example.com'
    ], [
        'name' => 'Admin',
        'password' => bcrypt('secret')
    ]);

    $role = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $user->roles()->syncWithoutDetaching([$role->id]);
    echo "CREATED\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
