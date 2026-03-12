<?php

namespace PredatorStudio\LiveTable\Cells;

use PredatorStudio\LiveTable\Enums\MoneyFormat;

class MoneyCell extends Cell
{
    public function __construct(
        private readonly MoneyFormat|string $format = MoneyFormat::SPACE_COMMA,
        private readonly string $currency = '',
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        [$decSep, $thousSep] = $this->separators();

        $formatted = number_format((float) $value, 2, $decSep, $thousSep);

        $currency = $this->currency !== '' ? ' '.e($this->currency) : '';

        return e($formatted).$currency;
    }

    private function separators(): array
    {
        $key = $this->format instanceof MoneyFormat ? $this->format->value : $this->format;

        return match ($key) {
            'space_dot' => ['.', ' '],
            'nospace_dot' => ['.', ''],
            'nospace_comma' => [',', ''],
            default => [',', ' '],  // space_comma
        };
    }
}
