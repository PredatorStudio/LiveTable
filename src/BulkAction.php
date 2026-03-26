<?php

namespace PredatorStudio\LiveTable;

class BulkAction
{
    public string $method;  // Livewire method to call
    public string $label;   // tooltip text
    public string $icon    = ''; // raw SVG string
    public string $tooltip = ''; // optional explicit tooltip (falls back to $label)

    private function __construct(string $method, string $label)
    {
        $this->method = $method;
        $this->label  = $label;
    }

    public static function make(string $method, string $label): static
    {
        return new static($method, $label);
    }

    public function icon(string $icon): static
    {
        $clone       = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function tooltip(string $tooltip): static
    {
        $clone          = clone $this;
        $clone->tooltip = $tooltip;

        return $clone;
    }
}
