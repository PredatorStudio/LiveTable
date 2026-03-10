<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Support\ServiceProvider;
use PredatorStudio\LiveTable\Commands\InstallCommand;

class LiveTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/live-table.php', 'live-table');
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
