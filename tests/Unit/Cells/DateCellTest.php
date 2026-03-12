<?php

use Carbon\Carbon;
use PredatorStudio\LiveTable\Cells\DateCell;
use PredatorStudio\LiveTable\Enums\DateFormat;

it('renders date with enum format', function () {
    $cell = new DateCell(DateFormat::DMY);
    expect($cell->render((object) [], Carbon::parse('2026-03-08')))->toBe('08.03.2026');
});

it('renders date with string format', function () {
    $cell = new DateCell('Y/m/d');
    expect($cell->render((object) [], Carbon::parse('2026-03-08')))->toBe('2026/03/08');
});

it('accepts carbon instance', function () {
    $cell = new DateCell(DateFormat::YMD);
    $carbon = Carbon::create(2026, 1, 15);
    expect($cell->render((object) [], $carbon))->toBe('2026-01-15');
});

it('accepts date string', function () {
    $cell = new DateCell(DateFormat::DMY);
    expect($cell->render((object) [], '2026-03-08'))->toBe('08.03.2026');
});

it('renders empty for null', function () {
    $cell = new DateCell(DateFormat::DMY);
    expect($cell->render((object) [], null))->toContain('—');
});
