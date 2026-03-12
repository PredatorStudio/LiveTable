<?php

use PredatorStudio\LiveTable\Enums\TimeFormat;

it('has correct string values', function () {
    expect(TimeFormat::HI->value)->toBe('H:i');
    expect(TimeFormat::HIS->value)->toBe('H:i:s');
});
