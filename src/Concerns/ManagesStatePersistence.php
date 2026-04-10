<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Support\Str;
use PredatorStudio\LiveTable\Contracts\TableStateRepositoryInterface;

trait ManagesStatePersistence
{
    /**
     * Optional injected repository. When null, resolved from the IoC container.
     * Can be set directly in tests to inject a mock:
     *   $table->stateRepository = $mock;
     */
    protected ?TableStateRepositoryInterface $stateRepository = null;

    private function getStateRepository(): TableStateRepositoryInterface
    {
        return $this->stateRepository ??= app(TableStateRepositoryInterface::class);
    }

    public function saveState(): void
    {
        if (! $this->persistState) {
            return;
        }

        $identifier = $this->resolveClientIdentifier();

        $this->getStateRepository()->save(
            $this->getTableIdentifier(),
            $identifier,
            [
                'search'         => $this->search,
                'active_filters' => $this->activeFilters,
                'column_order'   => $this->columnOrder,
                'hidden_columns' => $this->hiddenColumns,
                'per_page'       => $this->perPage,
                'sort_by'        => $this->sortBy,
                'sort_dir'       => $this->sortDir,
                'column_widths'  => $this->columnWidths,
            ],
        );
    }

    private function getTableIdentifier(): string
    {
        return $this->tableId !== '' ? $this->tableId : static::class;
    }

    private function resolveClientIdentifier(): array
    {
        if (auth()->check()) {
            return ['user_id' => auth()->id(), 'client_id' => null];
        }

        $clientId = session('live_table_client_id');

        if (! $clientId) {
            $clientId = (string) Str::uuid();
            session(['live_table_client_id' => $clientId]);
        }

        return ['user_id' => null, 'client_id' => $clientId];
    }

    private function loadState(): void
    {
        if (! $this->persistState) {
            return;
        }

        $identifier = $this->resolveClientIdentifier();

        $data = $this->getStateRepository()->load(
            $this->getTableIdentifier(),
            $identifier,
        );

        if ($data === null) {
            return;
        }

        $this->search        = $data['search'] ?? $this->search;
        $this->activeFilters = $data['active_filters'] ?? $this->activeFilters;
        $this->columnOrder   = $data['column_order'] ?? $this->columnOrder;
        $this->hiddenColumns = $data['hidden_columns'] ?? $this->hiddenColumns;
        $this->perPage       = $data['per_page'] ?? $this->perPage;
        $this->sortBy        = $data['sort_by'] ?? $this->sortBy;
        $this->sortDir       = $data['sort_dir'] ?? $this->sortDir;
        $this->columnWidths  = $data['column_widths'] ?? $this->columnWidths;
    }
}
