<?php

namespace PredatorStudio\LiveTable\Cells;

use Carbon\Carbon;
use PredatorStudio\LiveTable\Enums\DateFormat;

class DateCell extends Cell
{
    public function __construct(
        private readonly DateFormat|string $format = DateFormat::DMY,
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        $fmt = $this->format instanceof DateFormat ? $this->format->value : $this->format;

        return e(Carbon::parse($value)->format($fmt));
    }
}
