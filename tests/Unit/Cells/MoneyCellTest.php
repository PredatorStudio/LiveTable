<?php

use PredatorStudio\LiveTable\Cells\MoneyCell;
use PredatorStudio\LiveTable\Enums\MoneyFormat;

it('renders with space comma format', function () {
    $cell = new MoneyCell(MoneyFormat::SPACE_COMMA, 'PLN');
    expect($cell->render((object) [], 10000.00))->toBe('10 000,00 PLN');
});

it('renders with space dot format', function () {
    $cell = new MoneyCell(MoneyFormat::SPACE_DOT, 'USD');
    expect($cell->render((object) [], 10000.00))->toBe('10 000.00 USD');
});

it('renders with nospace comma format', function () {
    $cell = new MoneyCell(MoneyFormat::NOSPACE_COMMA, 'EUR');
    expect($cell->render((object) [], 10000.00))->toBe('10000,00 EUR');
});

it('renders with nospace dot format', function () {
    $cell = new MoneyCell(MoneyFormat::NOSPACE_DOT, 'GBP');
    expect($cell->render((object) [], 9999.99))->toBe('9999.99 GBP');
});

it('renders empty for null', function () {
    $cell = new MoneyCell(MoneyFormat::SPACE_COMMA, 'PLN');
    expect($cell->render((object) [], null))->toContain('—');
});
