<?php

namespace PredatorStudio\LiveTable\Contracts;

interface CellInterface
{
    /**
     * Render the cell as HTML for display in the table.
     */
    public function render(mixed $row, mixed $value): string;

    /**
     * Render a plain-text value suitable for CSV export (no HTML).
     */
    public function renderPlain(mixed $row, mixed $value): string;
}