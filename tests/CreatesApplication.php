<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Ensure APP_KEY is set for testing environment
        if (empty($app['config']->get('app.key'))) {
            $random = base64_encode(random_bytes(32));
            $app['config']->set('app.key', 'base64:'.$random);
        }

        return $app;
    }
}
