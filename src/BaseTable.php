<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

abstract class BaseTable extends Component
{
    public string $search  = '';
    public int    $perPage = 25;
    public int    $page    = 1;
    public string $sortBy  = '';
    public string $sortDir = 'asc';

    public array $hiddenColumns    = [];
    public array $columnOrder      = [];
    public array $activeFilters    = [];
    public bool  $showFiltersModal = false;

    public array $selected = [];

    private ?array $columnsCache = null;

    /** Enable per-row checkboxes and bulk actions zone. */
    protected bool $selectable = false;

    /** Primary key used for row selection. */
    protected string $primaryKey = 'id';

    /** Show the search input in the top bar. */
    protected bool $displaySearch = true;

    /** Show the column visibility/reorder button in the top bar. */
    protected bool $displayColumnList = true;

    /**
     * Base Eloquent query before search/filter/sort is applied.
     * Override applySearch() and applyFilters() to add logic.
     */
    abstract protected function baseQuery(): Builder;

    /**
     * Column definitions.
     *
     * Example:
     *   return [
     *       Column::make('name', 'Firstname')->sortable(),
     *       Column::make('email', 'E-mail')->sortable(),
     *       Column::make('created_at', 'Date')->sortable()->format(
     *           fn($row, $v) => e($v->format('d.m.Y'))
     *       ),
     *   ];
     *
     * @return Column[]
     */
    abstract public function columns(): array;

    // -------------------------------------------------------------------------
    // Optional overrides
    // -------------------------------------------------------------------------

    /**
     * Filter definitions rendered in the filters modal.
     * Apply them to the query inside applyFilters().
     *
     * @return Filter[]
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Bulk action buttons shown when rows are selected (top-left zone).
     *
     * @return BulkAction[]
     */
    public function bulkActions(): array
    {
        return [];
    }

    /**
     * Action buttons shown in the top-right zone (next to search).
     *
     * @return Action[]
     */
    public function headerActions(): array
    {
        return [];
    }

    /**
     * Apply full-text search to the query. Called only when $search is non-empty.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query;
    }

    /**
     * Apply active filter values ($this->activeFilters) to the query.
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Initialise column order and default hidden columns.
     * If your subclass needs mount(), call parent::mount() first.
     */
    public function mount(): void
    {
        $this->columnsCache = null;
        $cols = $this->cachedColumns();

        $this->columnOrder = array_column($cols, 'key');

        $this->hiddenColumns = array_values(array_column(
            array_filter($cols, fn(Column $c) => ! $c->visible),
            'key',
        ));
    }

    private function cachedColumns(): array
    {
        return $this->columnsCache ??= $this->columns();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function sort(string $column): void
    {
        $sortable = array_column(
            array_filter($this->cachedColumns(), fn(Column $c) => $c->sortable),
            'key',
        );

        if (! in_array($column, $sortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        }

        $this->page = 1;
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function toggleColumn(string $key): void
    {
        $valid = array_column($this->cachedColumns(), 'key');

        if (! in_array($key, $valid, true)) {
            return;
        }

        if (in_array($key, $this->hiddenColumns, true)) {
            $this->hiddenColumns = array_values(
                array_filter($this->hiddenColumns, fn($k) => $k !== $key),
            );
        } else {
            $this->hiddenColumns[] = $key;
        }
    }

    public function reorderColumns(array $order): void
    {
        $allowed   = array_column($this->cachedColumns(), 'key');
        $sanitized = array_values(array_intersect($order, $allowed));

        foreach ($allowed as $key) {
            if (! in_array($key, $sanitized, true)) {
                $sanitized[] = $key;
            }
        }

        $this->columnOrder = $sanitized;
    }

    public function applyActiveFilters(): void
    {
        $this->showFiltersModal = false;
        $this->page             = 1;
    }

    public function clearFilters(): void
    {
        $this->activeFilters    = [];
        $this->showFiltersModal = false;
        $this->page             = 1;
    }

    public function removeFilter(string $key): void
    {
        $filters = $this->activeFilters;
        unset($filters[$key]);
        $this->activeFilters = $filters;
        $this->page          = 1;
    }

    public function updateCell(string $rowId, string $columnKey, mixed $value): void
    {
        $col = collect($this->cachedColumns())->firstWhere('key', $columnKey);

        if ($col === null) {
            return;
        }

        $cell = $col->getCell();

        if (! ($cell instanceof \PredatorStudio\LiveTable\Cells\EditableCell)) {
            return;
        }

        $cell->validate($value);

        $row = $this->baseQuery()->where($this->primaryKey, $rowId)->firstOrFail();
        $cell->update($row, $value);
    }

    public function toggleSelectRow(string $id): void
    {
        if (in_array($id, $this->selected, true)) {
            $this->selected = array_values(
                array_filter($this->selected, fn($s) => $s !== $id),
            );
        } else {
            $this->selected[] = $id;
        }
    }

    public function selectRows(array $ids): void
    {
        $this->selected = array_values(array_unique(array_merge(
            $this->selected,
            array_map('strval', $ids),
        )));
    }

    public function deselectRows(array $ids): void
    {
        $ids            = array_map('strval', $ids);
        $this->selected = array_values(
            array_filter($this->selected, fn($id) => ! in_array($id, $ids, true)),
        );
    }

    private function resolvedColumns(): array
    {
        $cols    = collect($this->cachedColumns())->keyBy('key');
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
            fn(Column $c) => ! in_array($c->key, $this->hiddenColumns, true),
        ));
    }

    private function buildQuery(): Builder
    {
        $query = $this->baseQuery();

        if ($this->search !== '') {
            $query = $this->applySearch($query, trim($this->search));
        }

        return $this->applyFilters($query);
    }

    private function buildPageLinks(int $lastPage): array
    {
        if ($lastPage <= 1) {
            return [];
        }

        $current = $this->page;
        $keep    = [1, $lastPage];

        for ($i = max(2, $current - 2); $i <= min($lastPage - 1, $current + 2); $i++) {
            $keep[] = $i;
        }

        sort($keep);
        $keep = array_unique($keep);

        $result = [];
        $prev   = null;

        foreach ($keep as $p) {
            if ($prev !== null && $p - $prev > 1) {
                $result[] = '...';
            }

            $result[] = $p;
            $prev     = $p;
        }

        return $result;
    }

    /** @codeCoverageIgnore – requires full Livewire + Blade view stack */
    public function render(): mixed
    {
        $query    = $this->buildQuery();
        $total    = $query->count();
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        if ($this->page > $lastPage) {
            $this->page = $lastPage;
        }

        if (! empty($this->sortBy)) {
            $query->orderBy($this->sortBy, $this->sortDir);
        }

        $items = $query
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        $from = $total > 0 ? ($this->page - 1) * $this->perPage + 1 : 0;
        $to   = min($total, $this->page * $this->perPage);

        return view('live-table::base-table', [
            'items'            => $items,
            'total'            => $total,
            'lastPage'         => $lastPage,
            'from'             => $from,
            'to'               => $to,
            'pages'            => $this->buildPageLinks($lastPage),
            'visibleColumns'   => $this->visibleColumns(),
            'allColumns'       => $this->resolvedColumns(),
            'filterDefs'       => $this->filters(),
            'bulkActionDefs'   => $this->bulkActions(),
            'headerActionDefs' => $this->headerActions(),
            'selectable'        => $this->selectable,
            'primaryKey'        => $this->primaryKey,
            'currentPageIds'    => $items->pluck($this->primaryKey)->map('strval')->all(),
            'displaySearch'     => $this->displaySearch,
            'displayColumnList' => $this->displayColumnList,
        ]);
    }
}
