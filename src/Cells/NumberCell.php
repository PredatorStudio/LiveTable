<?php

namespace PredatorStudio\LiveTable\Cells;

class NumberCell extends Cell
{
    public function __construct(
        private readonly int $decimals = 0,
        private readonly string $decimalSeparator = ',',
        private readonly string $thousandSeparator = ' ',
        private readonly string $prefix = '',
        private readonly string $suffix = '',
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        $formatted = number_format((float) $value, $this->decimals, $this->decimalSeparator, $this->thousandSeparator);

        return e($this->prefix.$formatted.$this->suffix);
    }
}
