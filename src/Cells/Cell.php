<?php

namespace PredatorStudio\LiveTable\Cells;

abstract class Cell implements \PredatorStudio\LiveTable\Contracts\CellInterface
{
    abstract public function render(mixed $row, mixed $value): string;

    /**
     * Render a plain-text value for use in CSV export (no HTML).
     * Default: strip tags from render() output.
     */
    public function renderPlain(mixed $row, mixed $value): string
    {
        return strip_tags(html_entity_decode($this->render($row, $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected function renderEmpty(): string
    {
        return '<span class="text-muted">—</span>';
    }
}
