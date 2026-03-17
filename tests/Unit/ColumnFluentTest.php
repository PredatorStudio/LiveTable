<?php

use PredatorStudio\LiveTable\Column;

it('sortable() sets sortable flag to true', function () {
    $col = Column::make('name', 'Nazwa')->sortable();

    expect($col->sortable)->toBeTrue();
});

it('sortable() accepts explicit false', function () {
    $col = Column::make('name', 'Nazwa')->sortable(false);

    expect($col->sortable)->toBeFalse();
});

it('hidden() sets visible flag to false', function () {
    $col = Column::make('name', 'Nazwa')->hidden();

    expect($col->visible)->toBeFalse();
});

it('width() sets width property', function () {
    $col = Column::make('name', 'Nazwa')->width('150px');

    expect($col->width)->toBe('150px');
});

it('width() accepts rem and percent values', function () {
    expect(Column::make('a', 'A')->width('10rem')->width)->toBe('10rem');
    expect(Column::make('b', 'B')->width('20%')->width)->toBe('20%');
});

it('width() returns same instance for chaining', function () {
    $col = Column::make('name', 'Nazwa');
    $returned = $col->width('100px');

    expect($returned)->toBe($col);
});

it('width is null by default', function () {
    $col = Column::make('name', 'Nazwa');

    expect($col->width)->toBeNull();
});
