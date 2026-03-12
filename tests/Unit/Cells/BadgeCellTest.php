<?php

use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\Cells\BadgeCell;

uses(TestCase::class);

it('renders badge with mapped color', function () {
    $cell = new BadgeCell(['active' => 'success', 'banned' => 'danger']);
    $result = $cell->render((object) [], 'active');
    expect($result)->toContain('bg-success');
    expect($result)->toContain('active');
});

it('renders badge with mapped label and color', function () {
    $cell = new BadgeCell(['active' => ['color' => 'success', 'label' => 'Aktywny']]);
    $result = $cell->render((object) [], 'active');
    expect($result)->toContain('bg-success');
    expect($result)->toContain('Aktywny');
});

it('renders raw value when not in map', function () {
    $cell = new BadgeCell(['active' => 'success']);
    $result = $cell->render((object) [], 'unknown');
    expect($result)->toContain('secondary');
    expect($result)->toContain('unknown');
});

it('renders empty for null', function () {
    $cell = new BadgeCell([]);
    expect($cell->render((object) [], null))->toContain('—');
});

// Tailwind theme tests
it('renders tailwind badge with mapped color when theme is tailwind', function () {
    config(['live-table.theme' => 'tailwind']);

    $cell = new BadgeCell(['active' => 'success']);
    $result = $cell->render((object) [], 'active');

    expect($result)->toContain('bg-green-100');
    expect($result)->toContain('text-green-800');
    expect($result)->not->toContain('badge');
});

it('renders tailwind badge with fallback for unknown color', function () {
    config(['live-table.theme' => 'tailwind']);

    $cell = new BadgeCell(['active' => 'nonexistent-color']);
    $result = $cell->render((object) [], 'active');

    expect($result)->toContain('bg-gray-100');
});

it('renders tailwind badge for value not in map', function () {
    config(['live-table.theme' => 'tailwind']);

    $cell = new BadgeCell([]);
    $result = $cell->render((object) [], 'unknown');

    expect($result)->toContain('bg-gray-100');
    expect($result)->toContain('unknown');
});
