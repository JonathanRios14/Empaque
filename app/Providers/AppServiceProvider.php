<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->guardDestructiveDatabaseCommands();
    }

    private function guardDestructiveDatabaseCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $destructiveCommands = [
                'db:wipe',
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
            ];

            if (! in_array($event->command, $destructiveCommands, true)) {
                return;
            }

            if (filter_var(env('ALLOW_DESTRUCTIVE_DB_COMMANDS', false), FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            $connection = config('database.default');
            $database = (string) config("database.connections.{$connection}.database");

            if ($connection === 'sqlite' && $database === ':memory:') {
                return;
            }

            if (str_ends_with($database, '_testing')) {
                return;
            }

            throw new RuntimeException(
                "Refusing to run [{$event->command}] on database [{$database}]. " .
                'Set ALLOW_DESTRUCTIVE_DB_COMMANDS=true only when this is intentional.'
            );
        });
    }
}
