<?php

namespace PredatorStudio\LiveTable\Contracts;

interface TableStateRepositoryInterface
{
    /**
     * Persist the table state for the given table and client identifier.
     *
     * @param  array<string, mixed>  $identifier  e.g. ['user_id' => 1, 'client_id' => null]
     * @param  array<string, mixed>  $state
     */
    public function save(string $tableId, array $identifier, array $state): void;

    /**
     * Load the persisted state for the given table and client identifier.
     * Returns null when no state has been saved yet.
     *
     * @param  array<string, mixed>  $identifier
     * @return array<string, mixed>|null
     */
    public function load(string $tableId, array $identifier): ?array;
}