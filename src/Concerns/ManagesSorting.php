<?php

namespace PredatorStudio\LiveTable\Concerns;

use PredatorStudio\LiveTable\Column;

trait ManagesSorting
{
    public function sort(string $column): void
    {
        $this->selectAllQuery = false;

        $sortable = array_column(
            array_filter($this->cachedColumns(), fn (Column $c) => $c->sortable),
            'key',
        );

        if (! in_array($column, $sortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }

        $this->page = 1;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    private function safeSortBy(): string
    {
        if ($this->sortBy === '') {
            return '';
        }

        $sortable = array_column(
            array_filter($this->cachedColumns(), fn (Column $c) => $c->sortable),
            'key',
        );

        return in_array($this->sortBy, $sortable, true) ? $this->sortBy : '';
    }

    private function safeSortDir(): string
    {
        return $this->sortDir === 'desc' ? 'desc' : 'asc';
    }
}
