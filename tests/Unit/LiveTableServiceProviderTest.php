<?php

use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(\Orchestra\Testbench\TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

it('registers live-table view namespace', function () {
    expect(view()->exists('live-table::base-table'))->toBeTrue();
});

it('merges live-table config with default theme bootstrap', function () {
    expect(config('live-table.theme'))->toBe('bootstrap');
});

it('service provider boots without errors', function () {
    $provider = new LiveTableServiceProvider(app());
    $provider->boot();

    expect(true)->toBeTrue();
});

it('loads tailwind views when theme is tailwind', function () {
    config(['live-table.theme' => 'tailwind']);

    $provider = new LiveTableServiceProvider(app());
    $provider->boot();

    expect(view()->exists('live-table::base-table'))->toBeTrue();
});
