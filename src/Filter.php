<?php

namespace PredatorStudio\LiveTable;

class Filter
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type    = 'text',   // text | select | date
        public readonly array  $options = [],        // for type=select: ['value' => 'label']
    ) {}

    public static function text(string $key, string $label): static
    {
        return new static($key, $label, 'text');
    }

    /** @param array<string|int, string> $options */
    public static function select(string $key, string $label, array $options): static
    {
        return new static($key, $label, 'select', $options);
    }

    public static function date(string $key, string $label): static
    {
        return new static($key, $label, 'date');
    }
}
