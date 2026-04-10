<?php

namespace PredatorStudio\LiveTable;

use PredatorStudio\LiveTable\Enums\FilterType;

class Filter
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly FilterType $type = FilterType::TEXT,
        public readonly array $options = [],   // for type=select: ['value' => 'label']
    ) {
    }

    public static function text(string $key, string $label): static
    {
        return new static($key, $label, FilterType::TEXT);
    }

    /** @param array<string|int, string> $options */
    public static function select(string $key, string $label, array $options): static
    {
        return new static($key, $label, FilterType::SELECT, $options);
    }

    public static function date(string $key, string $label): static
    {
        return new static($key, $label, FilterType::DATE);
    }

    public static function number(string $key, string $label): static
    {
        return new static($key, $label, FilterType::NUMBER);
    }

    public static function numberRange(string $key, string $label): static
    {
        return new static($key, $label, FilterType::NUMBER_RANGE);
    }

    public static function dateRange(string $key, string $label): static
    {
        return new static($key, $label, FilterType::DATE_RANGE);
    }

    public static function datetime(string $key, string $label): static
    {
        return new static($key, $label, FilterType::DATETIME);
    }

    public static function datetimeRange(string $key, string $label): static
    {
        return new static($key, $label, FilterType::DATETIME_RANGE);
    }

    public static function time(string $key, string $label): static
    {
        return new static($key, $label, FilterType::TIME);
    }

    public static function boolean(string $key, string $label): static
    {
        return new static($key, $label, FilterType::BOOLEAN);
    }

    public static function money(string $key, string $label): static
    {
        return new static($key, $label, FilterType::MONEY);
    }

    /**
     * Normalize a money string to a float value for comparison.
     * Handles formats: "1 000,00 zł", "$1,234.56", "1.234,56", "1000.50", etc.
     * Returns null when the input cannot be parsed as a number.
     */
    public static function normalizeMoney(string $value): ?float
    {
        // Remove currency symbols, letters, and whitespace
        $cleaned = preg_replace('/[^\d,.]/', '', $value);

        if ($cleaned === null || $cleaned === '') {
            return null;
        }

        $hasComma = str_contains($cleaned, ',');
        $hasDot   = str_contains($cleaned, '.');

        if ($hasComma && $hasDot) {
            // Both separators present – determine which is decimal
            $lastComma = strrpos($cleaned, ',');
            $lastDot   = strrpos($cleaned, '.');

            if ($lastComma > $lastDot) {
                // European: 1.234,56 → remove dots, replace last comma with dot
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // American: 1,234.56 → remove commas
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif ($hasComma) {
            // Only comma: decimal (1,56) or thousands (1,234 or 1,234,567)
            $parts = explode(',', $cleaned);
            $last  = end($parts);

            if (count($parts) === 2 && strlen($last) <= 2) {
                // Decimal comma: "1234,56" or "1234,5"
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // Thousands comma: "1,234" or "1,234,567"
                $cleaned = str_replace(',', '', $cleaned);
            }
        }
        // Only dots: treat as-is (e.g. "1234.56" or "1.234" treated as decimal)

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
