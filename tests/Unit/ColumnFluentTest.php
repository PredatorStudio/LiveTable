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

it('sortable() returns same instance for chaining', function () {
    $col = Column::make('name', 'Nazwa');
    $returned = $col->sortable();

    expect($returned)->toBe($col);
});

it('hidden() sets visible flag to false', function () {
    $col = Column::make('name', 'Nazwa')->hidden();

    expect($col->visible)->toBeFalse();
});

it('hidden() returns same instance for chaining', function () {
    $col = Column::make('name', 'Nazwa');
    $returned = $col->hidden();

    expect($returned)->toBe($col);
});

it('column is visible by default', function () {
    $col = Column::make('name', 'Nazwa');

    expect($col->visible)->toBeTrue();
});

it('column is not sortable by default', function () {
    $col = Column::make('name', 'Nazwa');

    expect($col->sortable)->toBeFalse();
});
