<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Support\ServiceProvider;

class LiveTableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'live-table');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/live-table'),
        ], 'live-table-views');
    }
}
