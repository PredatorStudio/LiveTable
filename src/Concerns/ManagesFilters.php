<?php

namespace PredatorStudio\LiveTable\Concerns;

trait ManagesFilters
{
    public function applyActiveFilters(): void
    {
        $this->showFiltersModal = false;
        $this->page = 1;
        $this->selectAllQuery = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    public function clearFilters(): void
    {
        $this->activeFilters = [];
        $this->showFiltersModal = false;
        $this->page = 1;
        $this->selectAllQuery = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    public function removeFilter(string $key): void
    {
        $filters = $this->activeFilters;
        unset($filters[$key]);
        $this->activeFilters = $filters;
        $this->page = 1;
        $this->selectAllQuery = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }
}
