<?php

namespace PredatorStudio\LiveTable\Concerns;

trait ManagesSelection
{
    public function toggleSelectRow(string $id): void
    {
        if (in_array($id, $this->selected, true)) {
            $this->selected = array_values(
                array_filter($this->selected, fn($s) => $s !== $id),
            );
        } elseif (count($this->selected) < $this->maxSelected) {
            $this->selected[] = $id;
        }
    }

    public function selectRows(array $ids): void
    {
        $merged = array_values(array_unique(array_merge(
            $this->selected,
            array_map('strval', $ids),
        )));

        $this->selected = array_slice($merged, 0, $this->maxSelected);
    }

    public function deselectRows(array $ids): void
    {
        $ids            = array_map('strval', $ids);
        $this->selected = array_values(
            array_filter($this->selected, fn($id) => ! in_array($id, $ids, true)),
        );
    }
}