<?php

namespace PredatorStudio\LiveTable\Cells;

use Carbon\Carbon;
use PredatorStudio\LiveTable\Enums\TimeFormat;

class TimeCell extends Cell
{
    public function __construct(
        private readonly TimeFormat|string $format = TimeFormat::HI,
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        $fmt = $this->format instanceof TimeFormat ? $this->format->value : $this->format;

        return e(Carbon::parse($value)->format($fmt));
    }
}
