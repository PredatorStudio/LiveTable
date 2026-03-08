<?php

namespace PredatorStudio\LiveTable\Cells;

abstract class Cell
{
    abstract public function render(mixed $row, mixed $value): string;

    protected function renderEmpty(): string
    {
        return '<span class="text-muted">—</span>';
    }
}
