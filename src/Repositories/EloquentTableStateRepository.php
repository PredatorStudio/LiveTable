<?php

namespace PredatorStudio\LiveTable\Repositories;

use PredatorStudio\LiveTable\Contracts\TableStateRepositoryInterface;
use PredatorStudio\LiveTable\Models\TableState;

class EloquentTableStateRepository implements TableStateRepositoryInterface
{
    public function save(string $tableId, array $identifier, array $state): void
    {
        TableState::updateOrCreate(
            array_merge(['table_id' => $tableId], $identifier),
            ['state' => $state],
        );
    }

    public function load(string $tableId, array $identifier): ?array
    {
        $record = TableState::where('table_id', $tableId)
            ->where($identifier)
            ->first();

        return $record?->state;
    }
}