<?php

namespace PredatorStudio\LiveTable\Cells;

use Carbon\Carbon;
use PredatorStudio\LiveTable\Enums\DateTimeFormat;

class DateTimeCell extends Cell
{
    public function __construct(
        private readonly DateTimeFormat|string $format = DateTimeFormat::DMY_HM,
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        $fmt = $this->format instanceof DateTimeFormat ? $this->format->value : $this->format;

        return e(Carbon::parse($value)->format($fmt));
    }
}
