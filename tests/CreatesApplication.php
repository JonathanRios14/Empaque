<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection === 'sqlite' && $database === ':memory:') {
            return $app;
        }

        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "Refusing to run tests on database [{$database}]. Use sqlite :memory: or a *_testing database."
            );
        }

        return $app;
    }
}
