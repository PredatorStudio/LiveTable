<?php

use PredatorStudio\LiveTable\Enums\DateTimeFormat;

it('has correct string values', function () {
    expect(DateTimeFormat::DMY_HM->value)->toBe('d.m.Y H:i');
    expect(DateTimeFormat::DMY_HMS->value)->toBe('d.m.Y H:i:s');
    expect(DateTimeFormat::YMD_HM->value)->toBe('Y-m-d H:i');
});
