<?php

use PredatorStudio\LiveTable\Enums\DateFormat;

it('has correct string values', function () {
    expect(DateFormat::DMY->value)->toBe('d.m.Y');
    expect(DateFormat::YMD->value)->toBe('Y-m-d');
    expect(DateFormat::MDY->value)->toBe('m/d/Y');
});

it('can be used as string via value', function () {
    $fmt = DateFormat::DMY;
    expect($fmt->value)->toBeString();
});