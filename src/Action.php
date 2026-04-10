<?php

namespace PredatorStudio\LiveTable;

class Action
{
    public string $label;
    public string $method = ''; // Livewire method name
    public string $href = ''; // or a plain link
    public string $icon = ''; // raw SVG string

    private function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function method(string $method): static
    {
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    public function href(string $href): static
    {
        $clone = clone $this;
        $clone->href = $href;

        return $clone;
    }

    public function icon(string $icon): static
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }
}
