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
        $this->mergeConfigFrom(__DIR__.'/../config/live-table.php', 'live-table');

        // mergeConfigFrom is skipped when configurationIsCached() is true (e.g. in Testbench).
        // In real cached production environments the cache already contains merged defaults,
        // so this fallback only fires in environments where the cache exists but is empty.
        $config = $this->app->make('config');
        if ($config->get('live-table') === null) {
            $config->set('live-table', require __DIR__.'/../config/live-table.php');
        }

        $this->app->bind(TableStateRepositoryInterface::class, EloquentTableStateRepository::class);
    }

    public function boot(): void
    {
        $theme = config('live-table.theme', 'bootstrap');

        $this->loadViewsFrom(__DIR__.'/../resources/views/'.$theme, 'live-table');

        $this->publishes([
            __DIR__.'/../config/live-table.php' => config_path('live-table.php'),
        ], 'live-table-config');

        $this->publishes([
            __DIR__.'/../resources/views/bootstrap' => resource_path('views/vendor/live-table'),
        ], 'live-table-views-bootstrap');

        $this->publishes([
            __DIR__.'/../resources/views/tailwind' => resource_path('views/vendor/live-table'),
        ], 'live-table-views-tailwind');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'live-table-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
