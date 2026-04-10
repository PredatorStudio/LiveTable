<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubRows
{
    private array $items = [];
    private ?Builder $query = null;
    private bool $loaded = false;

    private function __construct()
    {
    }

    public static function fromArray(array $items): static
    {
        $instance = new static;
        $instance->items = $items;
        $instance->loaded = true;

        return $instance;
    }

    public static function fromCollection(Collection $collection): static
    {
        $instance = new static;
        $instance->items = $collection->all();
        $instance->loaded = true;

        return $instance;
    }

    /**
     * Store the query for lazy execution – get() is NOT called here.
     * The query runs the first time getItems() (or count()/isEmpty()) is called.
     */
    public static function fromQuery(Builder $query): static
    {
        $instance = new static;
        $instance->query = $query;

        return $instance;
    }

    public function getItems(): array
    {
        if (!$this->loaded && $this->query !== null) {
            $this->items = $this->query->get()->all();
            $this->loaded = true;
        }

        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->getItems());
    }

    public function count(): int
    {
        return count($this->getItems());
    }
}
