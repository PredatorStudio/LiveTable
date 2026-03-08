<?php

namespace PredatorStudio\LiveTable\Cells;

class TextCell extends Cell
{
    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        return e((string) $value);
    }
}
