<?php

namespace PredatorStudio\LiveTable;

class RowAction
{
    public string $label;
    public string $method = ''; // Livewire method called as method(primaryKey)
    public mixed $href = ''; // string or Closure($row): string
    public string $icon = ''; // raw SVG string
    public string $confirm = ''; // wire:confirm text

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

    /** @param string|\Closure(mixed $row): string $href */
    public function href(string|\Closure $href): static
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

    public function confirm(string $confirm): static
    {
        $clone = clone $this;
        $clone->confirm = $confirm;

        return $clone;
    }

    /**
     * Resolve the href for a specific row.
     * Returns an empty string when the action uses a Livewire method instead.
     */
    public function resolveHref(mixed $row): string
    {
        if ($this->href instanceof \Closure) {
            return (string)($this->href)($row);
        }

        return (string)$this->href;
    }
}
