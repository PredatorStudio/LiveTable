<?php

namespace PredatorStudio\LiveTable;

class BulkAction
{
    public function __construct(
        public readonly string $method, // Livewire method to call
        public readonly string $label,  // tooltip text
        public readonly string $icon = '',  // raw SVG string
    ) {}

    public static function make(string $method, string $label): static
    {
        return new static($method, $label);
    }

    public function icon(string $icon): static
    {
        return new static($this->method, $this->label, $icon);
    }
}
