<?php

namespace PredatorStudio\LiveTable\Cells;

use Closure;

class LinkCell extends Cell
{
    public function __construct(
        private readonly Closure $urlResolver,
        private readonly ?Closure $labelResolver = null,
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        $url = e(($this->urlResolver)($row));
        $label = $this->labelResolver !== null
            ? e((string) ($this->labelResolver)($row, $value))
            : e((string) $value);

        return "<a href=\"{$url}\">{$label}</a>";
    }
}
