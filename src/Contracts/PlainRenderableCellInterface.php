<?php

namespace PredatorStudio\LiveTable\Contracts;

/**
 * Cells that can render a plain-text value suitable for CSV/PDF export.
 * Separated from CellInterface following ISP – not every rendering context
 * requires a plain-text representation.
 */
interface PlainRenderableCellInterface extends CellInterface
{
    /**
     * Render a plain-text value suitable for CSV export (no HTML).
     */
    public function renderPlain(mixed $row, mixed $value): string;
}
