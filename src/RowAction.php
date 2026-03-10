<?php

namespace PredatorStudio\LiveTable;

class RowAction
{
    public function __construct(
        public readonly string $label,
        public readonly string $method  = '', // Livewire method called as method(primaryKey)
        public readonly mixed  $href    = '', // string or Closure($row): string
        public readonly string $icon    = '', // raw SVG string
        public readonly string $confirm = '', // wire:confirm text
    ) {}

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function method(string $method): static
    {
        return new static($this->label, $method, $this->href, $this->icon, $this->confirm);
    }

    /** @param  string|\Closure(mixed $row): string  $href */
    public function href(string|\Closure $href): static
    {
        return new static($this->label, $this->method, $href, $this->icon, $this->confirm);
    }

    public function icon(string $icon): static
    {
        return new static($this->label, $this->method, $this->href, $icon, $this->confirm);
    }

    public function confirm(string $confirm): static
    {
        return new static($this->label, $this->method, $this->href, $this->icon, $confirm);
    }

    /**
     * Resolve the href for a specific row.
     * Returns an empty string when the action uses a Livewire method instead.
     */
    public function resolveHref(mixed $row): string
    {
        if ($this->href instanceof \Closure) {
            return (string) ($this->href)($row);
        }

        return (string) $this->href;
    }
}