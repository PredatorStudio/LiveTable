<?php

use PredatorStudio\LiveTable\Enums\MoneyFormat;

it('has four format variants', function () {
    expect(MoneyFormat::cases())->toHaveCount(4);
});

it('space dot format has correct separators', function () {
    expect(MoneyFormat::SPACE_DOT->value)->toBe('space_dot');
});

it('all formats have string values', function () {
    foreach (MoneyFormat::cases() as $case) {
        expect($case->value)->toBeString();
    }
});
