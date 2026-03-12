<?php

namespace Tests\Feature;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class FeatureTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:Znl3l7m0GTT+lcSm7exEJumDbKEvHQDXRDgjRXWBrYA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LiveTableServiceProvider::class,
            LivewireServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../workbench/database/migrations');
    }
}
