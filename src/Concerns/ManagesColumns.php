<?php

namespace PredatorStudio\LiveTable\Concerns;

use PredatorStudio\LiveTable\Column;

trait ManagesColumns
{
    public function toggleColumn(string $key): void
    {
        $valid = array_column($this->cachedColumns(), 'key');

        if (! in_array($key, $valid, true)) {
            return;
        }

        if (in_array($key, $this->hiddenColumns, true)) {
            $this->hiddenColumns = array_values(
                array_filter($this->hiddenColumns, fn ($k) => $k !== $key),
            );
        } else {
            $this->hiddenColumns[] = $key;
        }

        $this->saveState();
    }

    public function reorderColumns(array $order): void
    {
        $allowed = array_column($this->cachedColumns(), 'key');
        $sanitized = array_values(array_intersect($order, $allowed));

        foreach ($allowed as $key) {
            if (! in_array($key, $sanitized, true)) {
                $sanitized[] = $key;
            }
        }

        $this->columnOrder = $sanitized;
        $this->saveState();
    }

    private function resolvedColumns(): array
    {
        $cols = collect($this->cachedColumns())->keyBy('key');
        $ordered = [];

        foreach ($this->columnOrder as $key) {
            if ($cols->has($key)) {
                $ordered[] = $cols[$key];
            }
        }

        foreach ($cols as $key => $col) {
            if (! in_array($key, $this->columnOrder, true)) {
                $ordered[] = $col;
            }
        }

        return $ordered;
    }

    private function visibleColumns(): array
    {
        return array_values(array_filter(
            $this->resolvedColumns(),
            fn (Column $c) => ! in_array($c->key, $this->hiddenColumns, true),
        ));
    }

    private function cachedColumns(): array
    {
        return $this->columnsCache ??= $this->columns();
    }
}
