<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubRows
{
    private array $items = [];

    private function __construct() {}

    public static function fromArray(array $items): static
    {
        $instance        = new static();
        $instance->items = $items;

        return $instance;
    }

    public static function fromCollection(Collection $collection): static
    {
        $instance        = new static();
        $instance->items = $collection->all();

        return $instance;
    }

    public static function fromQuery(Builder $query): static
    {
        $instance        = new static();
        $instance->items = $query->get()->all();

        return $instance;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
