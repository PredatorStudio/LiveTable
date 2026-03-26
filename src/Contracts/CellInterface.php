<?php

namespace PredatorStudio\LiveTable\Contracts;

interface CellInterface
{
    /**
     * Render the cell as HTML for display in the table.
     */
    public function render(mixed $row, mixed $value): string;
}