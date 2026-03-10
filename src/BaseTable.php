<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use PredatorStudio\LiveTable\Enums\AggregateScope;
use PredatorStudio\LiveTable\Enums\RowActionsMode;
use PredatorStudio\LiveTable\Enums\SelectMode;
use PredatorStudio\LiveTable\Models\TableState;
use PredatorStudio\LiveTable\SubRows;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Persist table state (search, filters, column order/visibility, sort, per-page) to the database.
     * Requires running the live-table migrations. Disabled by default – DB is completely optional.
     */
    public bool $persistState = false;

    /**
     * Table identifier used as the key in live_table_states.
     * Defaults to the fully qualified class name when empty.
     */
    public string $tableId = '';

    /**
     * Tracks how many rows are loaded in infinite scroll mode ($perPage === 0).
     * Not persisted to state – resets on every mount.
     */
    public int $loadedRows = 0;

    /**
     * Number of rows loaded per chunk in infinite scroll mode.
     * Override per table: protected int $infiniteChunkSize = 100;
     */
    protected int $infiniteChunkSize = 50;

    /**
     * Whether to show "Wszystkie" (infinite scroll) option in per-page selector.
     * Set to false on tables with very large datasets to prevent accidental use.
     */
    protected bool $allowInfiniteScroll = true;

    /**
     * Enable expandable sub-rows. When true, a narrow expand column is prepended
     * and subRows() is called per visible row to build the sub-row map.
     *
     * ⚠️ N+1 warning: eager-load relations in baseQuery() when using Eloquent relations.
     */
    protected bool $expandable = false;

    /**
     * Display mode for per-row actions column.
     * DROPDOWN – 3-dot button opens a dropdown list.
     * ICONS    – all action icons shown inline with tooltips.
     */
    protected RowActionsMode $rowActionsMode = RowActionsMode::DROPDOWN;

    /** Enable CSV export button. */
    protected bool $exportCsv = true;

    /** Enable PDF export button (skeleton – implement generatePdf()). */
    protected bool $exportPdf = false;

    /**
     * When true, a mass delete (trash icon) button appears in the top-left zone.
     * Requires $selectable = true. Calls beforeMassDelete() / afterMassDelete() hooks.
     */
    protected bool $massDelete = false;

    /**
     * When true, bulk actions and export operate on the full filtered query
     * instead of only $selected IDs. Set via selectAllFromQuery().
     */
    public bool $selectAllQuery = false;

    /** Enable per-row checkboxes and bulk actions zone. */
    protected bool $selectable = false;

    /**
     * How rows are selected when $selectable is true.
     * null             – read from config('live-table.select_mode') during mount()
     * SelectMode::CHECKBOX – dedicated checkbox column, always first, not reorderable/hideable
     * SelectMode::ROW      – clicking anywhere on the row toggles selection
     */
    protected ?SelectMode $selectMode = null;

    /** Primary key used for row selection. */
    protected string $primaryKey = 'id';

    /** Show the search input in the top bar. */
    protected bool $displaySearch = true;

    /** Show the column visibility/reorder button in the top bar. */
    protected bool $displayColumnList = true;

    /**
     * Column keys for which to display a SUM in the table footer.
     * Example: ['price', 'quantity']
     */
    protected array $sumColumns = [];

    /**
     * Column keys for which to display a COUNT (non-null rows) in the table footer.
     * Example: ['order_id', 'invoice_no']
     */
    protected array $countColumns = [];

    /**
     * Scope for footer aggregations.
     *  AggregateScope::ALL  – aggregate over the full filtered result set (ignores pagination)
     *  AggregateScope::PAGE – aggregate only over the currently displayed page
     */
    protected AggregateScope $aggregateScope = AggregateScope::ALL;

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
     * Per-row action buttons rendered in the last column.
     * Display mode is controlled by $rowActionsMode (DROPDOWN or ICONS).
     *
     * Example:
     *   return [
     *       RowAction::make('Edytuj')->href(fn($row) => route('users.edit', $row->id))->icon('<svg>…</svg>'),
     *       RowAction::make('Usuń')->method('deleteRow')->icon('<svg>…</svg>')->confirm('Na pewno usunąć?'),
     *   ];
     *
     * @return RowAction[]
     */
    public function rowActions(mixed $row): array
    {
        return [];
    }

    /**
     * Return sub-rows for the given main row.
     * Override in your table class and set $expandable = true.
     *
     * Example:
     *   protected function subRows(mixed $row): ?SubRows
     *   {
     *       return SubRows::fromCollection($row->projects);
     *   }
     */
    protected function subRows(mixed $row): ?SubRows
    {
        return null;
    }

    // -------------------------------------------------------------------------
    // Select all from query
    // -------------------------------------------------------------------------

    public function selectAllFromQuery(): void
    {
        $this->selectAllQuery = true;
    }

    public function clearSelectAllQuery(): void
    {
        $this->selectAllQuery = false;
        $this->selected       = [];
    }

    // -------------------------------------------------------------------------
    // Mass delete
    // -------------------------------------------------------------------------

    /**
     * Delete selected rows (or all filtered rows when $selectAllQuery is true).
     * Calls beforeMassDelete() before and afterMassDelete() after a successful deletion.
     * Throwing an exception inside beforeMassDelete() aborts the operation.
     */
    public function massDelete(): void
    {
        if (! $this->selectable || (! $this->selectAllQuery && empty($this->selected))) {
            return;
        }

        $query = $this->buildQuery();

        if (! $this->selectAllQuery) {
            $query->whereIn($this->primaryKey, $this->selected);
        }

        // IDs: for $selected already in memory; for selectAllQuery – one lightweight pluck()
        $ids = $this->selectAllQuery
            ? $query->pluck($this->primaryKey)->all()
            : $this->selected;

        $this->beforeMassDelete($ids);

        $query->delete();

        $this->afterMassDelete($ids);

        $this->selected       = [];
        $this->selectAllQuery = false;
        $this->page           = 1;
    }

    /**
     * Called before mass deletion. Throw an exception to abort the operation.
     * Useful for: authorization checks, logging the intent.
     *
     * If you need full model instances:
     *   $records = $this->baseQuery()->whereIn($this->primaryKey, $ids)->get();
     *
     * @param  array<int|string>  $ids
     */
    protected function beforeMassDelete(array $ids): void {}

    /**
     * Called only after a successful mass deletion.
     * Useful for: audit logs, dispatching jobs, sending notifications.
     *
     * Note: records no longer exist in the DB at this point.
     * Pass $ids to a queued job if you need async processing.
     *
     * @param  array<int|string>  $ids
     */
    protected function afterMassDelete(array $ids): void {}

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    public function exportCsv(): StreamedResponse
    {
        $columns  = $this->visibleColumns();
        $rows     = $this->getExportRows();
        $filename = class_basename(static::class) . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");

            fputcsv($output, array_map(fn(Column $col) => $col->label, $columns));

            foreach ($rows as $row) {
                fputcsv($output, $this->rowToCsvArray($row, $columns));

                if ($this->expandable) {
                    $sub = $this->subRows($row);
                    foreach ($sub?->getItems() ?? [] as $subRow) {
                        fputcsv($output, $this->rowToCsvArray($subRow, $columns));
                    }
                }
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function rowToCsvArray(mixed $row, array $columns): array
    {
        return array_map(
            fn(Column $col) => strip_tags(html_entity_decode($col->renderCell($row))),
            $columns,
        );
    }

    /**
     * Override to implement PDF export with your preferred library.
     *
     * Example with dompdf:
     *   $html = view('your-pdf-view', compact('rows', 'columns'))->render();
     *   return response($pdf->loadHtml($html)->output(), 200, [
     *       'Content-Type'        => 'application/pdf',
     *       'Content-Disposition' => 'attachment; filename="export.pdf"',
     *   ]);
     *
     * @param  Collection  $rows
     * @param  Column[]    $columns
     */
    protected function generatePdf(Collection $rows, array $columns): mixed
    {
        return null;
    }

    public function exportPdf(): mixed
    {
        if (! $this->exportPdf) {
            return null;
        }

        return $this->generatePdf($this->getExportRows(), $this->visibleColumns());
    }

    private function getExportRows(): Collection
    {
        $query = $this->buildQuery();

        if (! $this->selectAllQuery && ! empty($this->selected)) {
            $query->whereIn($this->primaryKey, $this->selected);
        }

        return $query->get();
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

    // -------------------------------------------------------------------------
    // State persistence
    // -------------------------------------------------------------------------

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

    public function saveState(): void
    {
        if (! $this->persistState) {
            return;
        }

        $identifier = $this->resolveClientIdentifier();

        TableState::updateOrCreate(
            array_merge(['table_id' => $this->getTableIdentifier()], $identifier),
            ['state' => [
                'search'         => $this->search,
                'active_filters' => $this->activeFilters,
                'column_order'   => $this->columnOrder,
                'hidden_columns' => $this->hiddenColumns,
                'per_page'       => $this->perPage,
                'sort_by'        => $this->sortBy,
                'sort_dir'       => $this->sortDir,
            ]],
        );
    }

    private function loadState(): void
    {
        if (! $this->persistState) {
            return;
        }

        $identifier = $this->resolveClientIdentifier();

        $record = TableState::where('table_id', $this->getTableIdentifier())
            ->where('user_id', $identifier['user_id'])
            ->where('client_id', $identifier['client_id'])
            ->first();

        if ($record === null) {
            return;
        }

        $data = $record->state;

        $this->search        = $data['search'] ?? $this->search;
        $this->activeFilters = $data['active_filters'] ?? $this->activeFilters;
        $this->columnOrder   = $data['column_order'] ?? $this->columnOrder;
        $this->hiddenColumns = $data['hidden_columns'] ?? $this->hiddenColumns;
        $this->perPage       = $data['per_page'] ?? $this->perPage;
        $this->sortBy        = $data['sort_by'] ?? $this->sortBy;
        $this->sortDir       = $data['sort_dir'] ?? $this->sortDir;
    }

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    /**
     * Initialise column order and default hidden columns.
     * If your subclass needs mount(), call parent::mount() first.
     */
    public function mount(): void
    {
        $this->selectMode ??= SelectMode::from(config('live-table.select_mode', SelectMode::CHECKBOX->value));

        $this->columnsCache = null;
        $cols = $this->cachedColumns();

        $this->columnOrder = array_column($cols, 'key');

        $this->hiddenColumns = array_values(array_column(
            array_filter($cols, fn(Column $c) => ! $c->visible),
            'key',
        ));

        $this->loadState();

        if ($this->perPage === 0 && $this->allowInfiniteScroll) {
            $this->loadedRows = $this->infiniteChunkSize;
        } elseif ($this->perPage === 0 && ! $this->allowInfiniteScroll) {
            $this->perPage    = 25;
            $this->loadedRows = 0;
        }
    }

    private function cachedColumns(): array
    {
        return $this->columnsCache ??= $this->columns();
    }

    public function updatedSearch(): void
    {
        $this->page           = 1;
        $this->selectAllQuery = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;

        if ($this->perPage === 0) {
            $this->loadedRows = $this->infiniteChunkSize;
        } else {
            $this->loadedRows = 0;
        }

        $this->saveState();
    }

    public function sort(string $column): void
    {
        $this->selectAllQuery = false;

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
        $this->resetInfiniteScroll();
        $this->saveState();
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

        $this->saveState();
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
        $this->saveState();
    }

    public function applyActiveFilters(): void
    {
        $this->showFiltersModal = false;
        $this->page             = 1;
        $this->selectAllQuery   = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    public function clearFilters(): void
    {
        $this->activeFilters    = [];
        $this->showFiltersModal = false;
        $this->page             = 1;
        $this->selectAllQuery   = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    public function removeFilter(string $key): void
    {
        $filters = $this->activeFilters;
        unset($filters[$key]);
        $this->activeFilters  = $filters;
        $this->page           = 1;
        $this->selectAllQuery = false;
        $this->resetInfiniteScroll();
        $this->saveState();
    }

    public function loadMore(): void
    {
        if ($this->perPage !== 0) {
            return;
        }

        $this->loadedRows += $this->infiniteChunkSize;
    }

    private function resetInfiniteScroll(): void
    {
        if ($this->perPage === 0) {
            $this->loadedRows = $this->infiniteChunkSize;
        }
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

    /**
     * Compute footer aggregates (sums and counts) based on $aggregateScope.
     *
     * @param  \Illuminate\Support\Collection  $pageItems  Already fetched current-page rows.
     * @return array{0: array<string,mixed>, 1: array<string,int>}  [sumData, countData]
     */
    private function computeAggregates(\Illuminate\Support\Collection $pageItems): array
    {
        $sumData   = [];
        $countData = [];

        if (empty($this->sumColumns) && empty($this->countColumns)) {
            return [$sumData, $countData];
        }

        if ($this->aggregateScope === AggregateScope::PAGE) {
            foreach ($this->sumColumns as $col) {
                $sumData[$col] = $pageItems->sum($col);
            }
            foreach ($this->countColumns as $col) {
                $countData[$col] = $pageItems->whereNotNull($col)->count();
            }
        } else {
            $aggQuery = $this->buildQuery();

            foreach ($this->sumColumns as $col) {
                $sumData[$col] = $aggQuery->sum($col);
            }
            foreach ($this->countColumns as $col) {
                $countData[$col] = $this->buildQuery()->whereNotNull($col)->count();
            }
        }

        return [$sumData, $countData];
    }

    /** @codeCoverageIgnore – requires full Livewire + Blade view stack */
    public function render(): mixed
    {
        $query = $this->buildQuery();
        $total = $query->count();

        if (! empty($this->sortBy)) {
            $query->orderBy($this->sortBy, $this->sortDir);
        }

        $infiniteMode = $this->perPage === 0;

        if ($infiniteMode) {
            $loadedRows = max($this->loadedRows, $this->infiniteChunkSize);
            $items      = $query->limit($loadedRows)->get();
            $allLoaded  = $loadedRows >= $total;
            $lastPage   = 1;
            $from       = $total > 0 ? 1 : 0;
            $to         = $items->count();
            $pages      = [];
        } else {
            $lastPage = max(1, (int) ceil($total / $this->perPage));

            if ($this->page > $lastPage) {
                $this->page = $lastPage;
            }

            $items     = $query
                ->offset(($this->page - 1) * $this->perPage)
                ->limit($this->perPage)
                ->get();
            $allLoaded = false;
            $from      = $total > 0 ? ($this->page - 1) * $this->perPage + 1 : 0;
            $to        = min($total, $this->page * $this->perPage);
            $pages     = $this->buildPageLinks($lastPage);
        }

        [$rawSumData, $countData] = $this->computeAggregates($items);

        $sumData = array_map(
            static fn(mixed $v) => is_float($v)
                ? number_format($v, 2, ',', ' ')
                : number_format((int) $v, 0, ',', ' '),
            $rawSumData,
        );

        $selectMode      = $this->selectMode ?? SelectMode::CHECKBOX;
        $hasCheckboxCol  = $this->selectable && $selectMode === SelectMode::CHECKBOX;
        $isRowSelectMode = $this->selectable && $selectMode === SelectMode::ROW;
        $visibleColumns  = $this->visibleColumns();

        $subRowsMap = [];
        if ($this->expandable) {
            foreach ($items as $item) {
                $key              = (string) data_get($item, $this->primaryKey);
                $sub              = $this->subRows($item);
                $subRowsMap[$key] = $sub ? $sub->getItems() : [];
            }
        }

        $hasRowActions = false;
        $rowActionsMap = [];
        foreach ($items as $item) {
            $key     = (string) data_get($item, $this->primaryKey);
            $actions = $this->rowActions($item);
            if (! empty($actions)) {
                $hasRowActions = true;
            }
            $rowActionsMap[$key] = array_map(
                fn (RowAction $a) => (object) [
                    'label'   => $a->label,
                    'icon'    => $a->icon,
                    'href'    => $a->resolveHref($item),
                    'method'  => $a->method,
                    'confirm' => $a->confirm,
                ],
                $actions,
            );
        }

        $colspan = count($visibleColumns) + ($hasCheckboxCol ? 1 : 0) + ($this->expandable ? 1 : 0) + ($hasRowActions ? 1 : 0);

        $currentPageIds  = $items->pluck($this->primaryKey)->map('strval')->all();
        $allPageSelected = ! empty($currentPageIds)
            && count(array_diff($currentPageIds, $this->selected)) === 0;

        return view('live-table::base-table', [
            'items'               => $items,
            'total'               => $total,
            'lastPage'            => $lastPage,
            'from'                => $from,
            'to'                  => $to,
            'pages'               => $pages,
            'visibleColumns'      => $visibleColumns,
            'allColumns'          => $this->resolvedColumns(),
            'filterDefs'          => $this->filters(),
            'bulkActionDefs'      => $this->bulkActions(),
            'headerActionDefs'    => $this->headerActions(),
            'selectable'          => $this->selectable,
            'hasCheckboxCol'      => $hasCheckboxCol,
            'isRowSelectMode'     => $isRowSelectMode,
            'primaryKey'          => $this->primaryKey,
            'currentPageIds'      => $currentPageIds,
            'displaySearch'       => $this->displaySearch,
            'displayColumnList'   => $this->displayColumnList,
            'sumData'             => $sumData,
            'countData'           => $countData,
            'infiniteMode'        => $infiniteMode,
            'allLoaded'           => $allLoaded,
            'allowInfiniteScroll' => $this->allowInfiniteScroll,
            'colspan'             => $colspan,
            'expandable'          => $this->expandable,
            'subRowsMap'          => $subRowsMap,
            'allPageSelected'     => $allPageSelected,
            'selectAllQuery'      => $this->selectAllQuery,
            'exportCsv'           => $this->exportCsv,
            'exportPdf'           => $this->exportPdf,
            'massDeleteEnabled'   => $this->massDelete,
            'hasRowActions'       => $hasRowActions,
            'rowActionsMap'       => $rowActionsMap,
            'rowActionsMode'      => $this->rowActionsMode,
        ]);
    }
}
