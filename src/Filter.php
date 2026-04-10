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
}