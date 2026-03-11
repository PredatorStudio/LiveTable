<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

abstract class FeatureTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:Znl3l7m0GTT+lcSm7exEJumDbKEvHQDXRDgjRXWBrYA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LiveTableServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \Workbench\App\Providers\WorkbenchServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../workbench/database/migrations');
    }
}