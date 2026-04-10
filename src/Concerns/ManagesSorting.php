<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Database\Eloquent\Builder;
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

    private function applySorting(Builder $query): void
    {
        if ($this->sortBy === '') {
            return;
        }

        $sortable = array_column(
            array_filter($this->cachedColumns(), fn (Column $c) => $c->sortable),
            'key',
        );

        if (in_array($this->sortBy, $sortable, true)) {
            $query->orderBy($this->sortBy, $this->sortDir === 'desc' ? 'desc' : 'asc');
        }
    }
}
