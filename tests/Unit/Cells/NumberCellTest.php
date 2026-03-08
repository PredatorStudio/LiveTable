<?php

use PredatorStudio\LiveTable\Cells\NumberCell;

it('renders integer', function () {
    $cell = new NumberCell();
    expect($cell->render((object)[], 42))->toBe('42');
});

it('renders float with decimals', function () {
    $cell = new NumberCell(decimals: 2);
    expect($cell->render((object)[], 1234.5))->toBe('1 234,50');
});

it('renders with prefix and suffix', function () {
    $cell = new NumberCell(decimals: 0, prefix: '#', suffix: ' szt.');
    expect($cell->render((object)[], 10))->toBe('#10 szt.');
});

it('renders empty for null', function () {
    $cell = new NumberCell();
    expect($cell->render((object)[], null))->toContain('—');
});
