<?php

use Carbon\Carbon;
use PredatorStudio\LiveTable\Cells\TimeCell;
use PredatorStudio\LiveTable\Enums\TimeFormat;

it('renders time with HI format', function () {
    $cell = new TimeCell(TimeFormat::HI);
    expect($cell->render((object)[], Carbon::parse('2026-03-08 14:30:45')))->toBe('14:30');
});

it('renders time with HIS format', function () {
    $cell = new TimeCell(TimeFormat::HIS);
    expect($cell->render((object)[], Carbon::parse('2026-03-08 09:05:07')))->toBe('09:05:07');
});

it('renders empty for null', function () {
    $cell = new TimeCell(TimeFormat::HI);
    expect($cell->render((object)[], null))->toContain('—');
});
