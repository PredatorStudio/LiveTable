<?php

use PredatorStudio\LiveTable\Enums\DateFormat;
use PredatorStudio\LiveTable\Enums\DateTimeFormat;
use PredatorStudio\LiveTable\Enums\MoneyFormat;
use PredatorStudio\LiveTable\Enums\TimeFormat;

it('date format enum has correct string values', function () {
    expect(DateFormat::DMY->value)->toBe('d.m.Y');
    expect(DateFormat::YMD->value)->toBe('Y-m-d');
});

it('datetime and time format enums have string values', function () {
    expect(DateTimeFormat::cases())->not->toBeEmpty();
    expect(TimeFormat::cases())->not->toBeEmpty();
    foreach (DateTimeFormat::cases() as $case) {
        expect($case->value)->toBeString();
    }
});

it('money format enum has four variants with string values', function () {
    expect(MoneyFormat::cases())->toHaveCount(4);
    expect(MoneyFormat::SPACE_DOT->value)->toBe('space_dot');
});
