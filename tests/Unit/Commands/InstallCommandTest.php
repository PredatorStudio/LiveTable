<?php

use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

// ---------------------------------------------------------------------------
// Bootstrap theme
// ---------------------------------------------------------------------------

it('installs successfully with bootstrap theme', function () {
    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'bootstrap',
            ['bootstrap', 'tailwind'],
        )
        ->assertExitCode(0);
});

it('shows success message for bootstrap install', function () {
    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'bootstrap',
            ['bootstrap', 'tailwind'],
        )
        ->expectsOutputToContain('LiveTable installed successfully.')
        ->assertExitCode(0);
});

it('does not show tailwind instructions for bootstrap theme', function () {
    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'bootstrap',
            ['bootstrap', 'tailwind'],
        )
        ->doesntExpectOutputToContain('tailwind.config.js')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Tailwind theme
// ---------------------------------------------------------------------------

it('installs successfully with tailwind theme', function () {
    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'tailwind',
            ['bootstrap', 'tailwind'],
        )
        ->assertExitCode(0);
});

it('shows tailwind content path instructions for tailwind theme', function () {
    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'tailwind',
            ['bootstrap', 'tailwind'],
        )
        ->expectsOutputToContain('tailwind.config.js')
        ->assertExitCode(0);
});

it('shows vendor path in tailwind instructions', function () {
    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'tailwind',
            ['bootstrap', 'tailwind'],
        )
        ->expectsOutputToContain('vendor/predatorstudio/live-table')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Config file theme update
// ---------------------------------------------------------------------------

it('updates theme to tailwind in published config file', function () {
    // Pre-create config file with bootstrap theme (as vendor:publish would produce)
    $configPath = config_path('live-table.php');
    @mkdir(dirname($configPath), 0755, true);
    file_put_contents($configPath, "<?php\nreturn ['theme' => 'bootstrap'];");

    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'tailwind',
            ['bootstrap', 'tailwind'],
        )
        ->assertExitCode(0);

    expect(file_get_contents($configPath))->toContain("'theme' => 'tailwind'");

    // Cleanup
    @unlink($configPath);
});

it('keeps bootstrap theme in config file when bootstrap chosen', function () {
    $configPath = config_path('live-table.php');
    @mkdir(dirname($configPath), 0755, true);
    file_put_contents($configPath, "<?php\nreturn ['theme' => 'bootstrap'];");

    $this->artisan('live-table:install')
        ->expectsChoice(
            'Which CSS framework would you like to use?',
            'bootstrap',
            ['bootstrap', 'tailwind'],
        )
        ->assertExitCode(0);

    expect(file_get_contents($configPath))->toContain("'theme' => 'bootstrap'");

    // Cleanup
    @unlink($configPath);
});
