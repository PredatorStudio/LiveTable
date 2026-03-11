<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use PredatorStudio\LiveTable\Concerns\ManagesAggregates;
use PredatorStudio\LiveTable\Concerns\ManagesBulkActions;
use PredatorStudio\LiveTable\Concerns\ManagesColumns;
use PredatorStudio\LiveTable\Concerns\ManagesDefaultCrud;
use PredatorStudio\LiveTable\Concerns\ManagesExport;
use PredatorStudio\LiveTable\Concerns\ManagesFilters;
use PredatorStudio\LiveTable\Concerns\ManagesInfiniteScroll;
use PredatorStudio\LiveTable\Concerns\ManagesMassActions;
use PredatorStudio\LiveTable\Concerns\ManagesSelection;
use PredatorStudio\LiveTable\Concerns\ManagesSorting;
use PredatorStudio\LiveTable\Concerns\ManagesStatePersistence;
use PredatorStudio\LiveTable\Enums\AggregateScope;
use PredatorStudio\LiveTable\Enums\RowActionsMode;
use PredatorStudio\LiveTable\Enums\SelectMode;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class BaseTable extends Component
{
    use ManagesInfiniteScroll,
        ManagesBulkActions,
        ManagesSelection,
        ManagesFilters,
        ManagesSorting,
        ManagesColumns,
        ManagesStatePersistence,
        ManagesAggregates,
        ManagesExport,
        ManagesMassActions,
        ManagesDefaultCrud;

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

    protected ?array $columnsCache = null;

    /** Static schema-type cache (per request). Key: "table.column" → HTML input type. */
    protected static array $schemaCache = [];

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
     * FQCN of the Eloquent model managed by this table (e.g. App\Models\User).
     * Required when $defaultCreating = true.
     */
    protected string $model = '';

    /**
     * When true, a "Dodaj rekord" button appears in the top-right zone.
     * Requires $model to be set.
     */
    protected bool $defaultCreating = false;

    /** Whether the default-creating modal is open. */
    public bool $showCreatingModal = false;

    /** Form data bound to the creating modal inputs. */
    public array $creatingData = [];

    /**
     * Enable default per-row actions (edit and/or delete).
     * Requires $model to be set for the edit action.
     */
    protected bool $defaultActions = false;

    /** Show the default "Edytuj" row action (opens editing modal). Requires $model. */
    protected bool $defaultActionEdit = true;

    /** Show the default "Usuń" row action (deletes the record with confirmation). */
    protected bool $defaultActionDelete = true;

    /** Primary key of the record currently open in the editing modal. */
    public string $editingId = '';

    /** Form data bound to the editing modal inputs. */
    public array $editingData = [];

    /** Whether the editing modal is open. */
    public bool $showEditingModal = false;

    /**
     * When true, a "Edytuj zaznaczone" button appears in the top-left zone.
     * Requires $selectable = true and $model to be set.
     * Empty form fields are ignored – only non-empty values are applied.
     */
    protected bool $massEdit = false;

    /** Form data bound to the mass-edit modal inputs. */
    public array $massEditData = [];

    /** Whether the mass-edit modal is open. */
    public bool $showMassEditModal = false;

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
     * Maximum number of rows that can be selected at once.
     * Prevents DoS via oversized $selected payload in Livewire state.
     */
    protected int $maxSelected = 10_000;

    /**
     * Whitelist of fillable field keys exposed in the creating modal.
     * When empty – all $fillable fields are used (default behaviour).
     *
     * ⚠️ Use this to prevent sensitive fields (is_admin, stripe_id …) from
     * being exposed and mass-assigned through the default creating form.
     */
    protected array $creatableFields = [];

    /**
     * Whitelist of fillable field keys exposed in the editing modal.
     * When empty – falls back to $creatableFields, then all $fillable.
     */
    protected array $editableFields = [];

    /**
     * When true, fields whose name matches 'password' or '*_password' are
     * automatically hashed with Hash::make() before being persisted.
     */
    protected bool $autoHashPasswords = true;

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
     * @return Column[]
     */
    abstract public function columns(): array;

    // -------------------------------------------------------------------------
    // Optional overrides
    // -------------------------------------------------------------------------

    /**
     * Filter definitions rendered in the filters modal.
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
     */
    protected function subRows(mixed $row): ?SubRows
    {
        return null;
    }

    // -------------------------------------------------------------------------
    // Authorization hook
    // -------------------------------------------------------------------------

    /**
     * Override to add authorization checks before any destructive action.
     * Throw \Illuminate\Auth\Access\AuthorizationException (or any exception) to abort.
     *
     * @param  string  $action  'create' | 'update' | 'delete' | 'massEdit' | 'massDelete'
     * @param  mixed   $record  Eloquent model instance, or null for mass operations
     */
    protected function authorizeAction(string $action, mixed $record = null): void {}

    // -------------------------------------------------------------------------
    // Search / Filter hooks
    // -------------------------------------------------------------------------

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
    // Core query pipeline
    // -------------------------------------------------------------------------

    protected function buildQuery(): Builder
    {
        $query = $this->baseQuery();

        if ($this->search !== '') {
            $query = $this->applySearch($query, trim($this->search));
        }

        return $this->applyFilters($query);
    }

    // -------------------------------------------------------------------------
    // Password hashing helper (used by ManagesDefaultCrud and ManagesMassActions)
    // -------------------------------------------------------------------------

    /**
     * Hash fields whose key matches 'password' or '*_password' before persistence.
     * Skips empty values and respects the $autoHashPasswords flag.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function hashPasswordFields(array $data): array
    {
        if (! $this->autoHashPasswords) {
            return $data;
        }

        foreach (array_keys($data) as $key) {
            if ($data[$key] !== '' && $data[$key] !== null && Str::is(['password', '*_password'], $key)) {
                $data[$key] = \Illuminate\Support\Facades\Hash::make((string) $data[$key]);
            }
        }

        return $data;
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

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
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

    // -------------------------------------------------------------------------
    // Pagination helper
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    /** @codeCoverageIgnore – requires full Livewire + Blade view stack */
    public function render(): mixed
    {
        $query = $this->buildQuery();
        $total = $query->count();

        $safeSortBy = $this->safeSortBy();
        if ($safeSortBy !== '') {
            $query->orderBy($safeSortBy, $this->safeSortDir());
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

        $canEdit   = $this->defaultActions && $this->defaultActionEdit
                        && $this->model !== '' && class_exists($this->model);
        $canDelete = $this->defaultActions && $this->defaultActionDelete;

        $hasRowActions = false;
        $rowActionsMap = [];
        foreach ($items as $item) {
            $key     = (string) data_get($item, $this->primaryKey);
            $actions = $this->rowActions($item);

            if ($canEdit) {
                $actions[] = RowAction::make('Edytuj')
                    ->method('openEditingModal')
                    ->icon('<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>');
            }

            if ($canDelete) {
                $actions[] = RowAction::make('Usuń')
                    ->method('deleteRow')
                    ->icon('<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>')
                    ->confirm('Czy na pewno chcesz usunąć ten rekord?');
            }

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

        $canCreate      = $this->defaultCreating && $this->model !== '' && class_exists($this->model);
        $creatingFields = $canCreate ? $this->creatingFields() : [];

        // Editing modal shares the same field definitions as creating modal
        $editingFields = ($canEdit && ! empty($creatingFields)) ? $creatingFields : (
            $canEdit ? $this->creatingFields() : []
        );

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
            'massEditEnabled'     => $this->massEdit && $this->selectable
                                        && $this->model !== '' && class_exists($this->model)
                                        && ! empty($creatingFields ?: $this->creatingFields()),
            'showMassEditModal'   => $this->showMassEditModal,
            'massEditFields'      => $this->massEdit ? ($creatingFields ?: $this->creatingFields()) : [],
            'canCreate'           => $canCreate && ! empty($creatingFields),
            'creatingFields'      => $creatingFields,
            'showCreatingModal'   => $this->showCreatingModal,
            'canEdit'             => $canEdit,
            'editingFields'       => $editingFields,
            'showEditingModal'    => $this->showEditingModal,
        ]);
    }
}