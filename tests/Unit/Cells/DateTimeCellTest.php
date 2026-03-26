<?php

use Carbon\Carbon;
use PredatorStudio\LiveTable\Cells\DateTimeCell;
use PredatorStudio\LiveTable\Enums\DateTimeFormat;

it('renders datetime with enum format', function () {
    $cell = new DateTimeCell(DateTimeFormat::DMY_HM);
    expect($cell->render((object) [], Carbon::parse('2026-03-08 14:30:00')))->toBe('08.03.2026 14:30');
});

it('renders datetime with string format', function () {
    $cell = new DateTimeCell('Y-m-d H:i:s');
    expect($cell->render((object) [], Carbon::parse('2026-03-08 09:05:00')))->toBe('2026-03-08 09:05:00');
});

it('renders empty for null', function () {
    $cell = new DateTimeCell(DateTimeFormat::DMY_HM);
    expect($cell->render((object) [], null))->toContain('—');
});
