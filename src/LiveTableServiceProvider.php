<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Support\ServiceProvider;
use PredatorStudio\LiveTable\Commands\InstallCommand;
use PredatorStudio\LiveTable\Contracts\TableStateRepositoryInterface;
use PredatorStudio\LiveTable\Repositories\EloquentTableStateRepository;

class LiveTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/live-table.php', 'live-table');

        // mergeConfigFrom is a no-op when configurationIsCached() is true (e.g. in Testbench).
        // Replicate the same merge manually so defaults are always available.
        if ($this->app->configurationIsCached()) {
            $config = $this->app->make('config');
            $defaults = require __DIR__ . '/../config/live-table.php';
            $config->set('live-table', array_merge($defaults, $config->get('live-table', [])));
        }

        $this->app->bind(TableStateRepositoryInterface::class, EloquentTableStateRepository::class);
    }

    public function boot(): void
    {
        $theme = config('live-table.theme', 'bootstrap');

        $this->loadViewsFrom(__DIR__ . '/../resources/views/' . $theme, 'live-table');

        $this->publishes([
            __DIR__ . '/../config/live-table.php' => config_path('live-table.php'),
        ], 'live-table-config');

        $this->publishes([
            __DIR__ . '/../resources/views/bootstrap' => resource_path('views/vendor/live-table'),
        ], 'live-table-views-bootstrap');

        $this->publishes([
            __DIR__ . '/../resources/views/tailwind' => resource_path('views/vendor/live-table'),
        ], 'live-table-views-tailwind');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'live-table-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
