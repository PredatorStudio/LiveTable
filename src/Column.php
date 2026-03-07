<?php

namespace PredatorStudio\LiveTable;

use Closure;

class Column
{
    public bool $sortable = false;
    public bool $visible  = true;

    private ?Closure $formatter = null;

    public function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {}

    public static function make(string $key, string $label): static
    {
        return new static($key, $label);
    }

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;

        return $this;
    }

    public function hidden(): static
    {
        $this->visible = false;

        return $this;
    }

    /**
     * Custom cell renderer. Receives ($row, $value) and should return an HTML string.
     * Use e() to escape if needed. Returned value is rendered unescaped ({!! !!}).
     */
    public function format(Closure $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function renderCell(mixed $row): string
    {
        $value = data_get($row, $this->key);

        if ($this->formatter !== null) {
            return (string) ($this->formatter)($row, $value);
        }

        if ($value === null || $value === '') {
            return '<span class="text-muted">—</span>';
        }

        return e((string) $value);
    }
}
