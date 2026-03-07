<?php

namespace PredatorStudio\LiveTable;

class Action
{
    public function __construct(
        public readonly string $label,
        public readonly string $method = '', // Livewire method name
        public readonly string $href   = '', // or a plain link
        public readonly string $icon   = '', // raw SVG string
    ) {}

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function method(string $method): static
    {
        return new static($this->label, $method, $this->href, $this->icon);
    }

    public function href(string $href): static
    {
        return new static($this->label, $this->method, $href, $this->icon);
    }

    public function icon(string $icon): static
    {
        return new static($this->label, $this->method, $this->href, $icon);
    }
}
