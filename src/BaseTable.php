<?php

namespace PredatorStudio\LiveTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use PredatorStudio\LiveTable\Contracts\EditableCellInterface;
use PredatorStudio\LiveTable\Concerns\ManagesAggregates;
use PredatorStudio\LiveTable\Concerns\ManagesBulkActions;
use PredatorStudio\LiveTable\Concerns\ManagesColumns;
use PredatorStudio\LiveTable\Concerns\ManagesCreating;
use PredatorStudio\LiveTable\Concerns\ManagesDeletion;
use PredatorStudio\LiveTable\Concerns\ManagesEditing;
use PredatorStudio\LiveTable\Concerns\ManagesExport;
use PredatorStudio\LiveTable\Concerns\ManagesFilters;
use PredatorStudio\LiveTable\Concerns\ManagesInfiniteScroll;
use PredatorStudio\LiveTable\Concerns\ManagesMassActions;
use PredatorStudio\LiveTable\Concerns\ManagesPagination;
use PredatorStudio\LiveTable\Concerns\ManagesRowActions;
use PredatorStudio\LiveTable\Concerns\ManagesSelection;
use PredatorStudio\LiveTable\Concerns\ManagesSorting;
use PredatorStudio\LiveTable\Concerns\ManagesStatePersistence;
use PredatorStudio\LiveTable\Enums\AggregateScope;
use PredatorStudio\LiveTable\Enums\RowActionsMode;
use PredatorStudio\LiveTable\Enums\SelectMode;

abstract class BaseTable extends Component
{
    use ManagesAggregates,
        ManagesBulkActions,
        ManagesColumns,
        ManagesCreating,
        ManagesDeletion,
        ManagesEditing,
        ManagesExport,
        ManagesFilters,
        ManagesInfiniteScroll,
        ManagesMassActions,
        ManagesPagination,
        ManagesRowActions,
        ManagesSelection,
        ManagesSorting,
        ManagesStatePersistence;

    public string $search = '';

    public int $perPage = 25;

    public int $page = 1;

    public string $sortBy = '';

    public string $sortDir = 'asc';

    public array $hiddenColumns = [];

    public array $columnOrder = [];

    public array $activeFilters = [];

    public bool $showFiltersModal = false;

    public array $selected = [];

    protected ?array $columnsCache = null;

    /** Static schema-type cache (per request). Key: "table.column" → HTML input type. */
    protected static array $schemaCache = [];

    /**
     * Persist table state (search, filters, column order/visibility, sort, per-page) to the database.
     * Requires running the live-table migrations. Disabled by default – DB is completely optional.
     */
    protected bool $persistState = false;

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
     * Whether to show "All" (infinite scroll) option in per-page selector.
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
     * When true, each sub-row gets a checkbox for individual selection.
     * Sub-row selections are tracked in $selectedSubRows.
     */
    protected bool $subRowsSelectable = false;

    /** Primary key field used to identify sub-row items. */
    protected string $subRowPrimaryKey = 'id';

    /** Currently selected sub-row IDs (string primary key values). */
    public array $selectedSubRows = [];

    /**
     * When true, sub-rows get a 3-dot actions dropdown.
     * Override subRowActions() to define the actions per sub-row.
     */
    protected bool $subRowsHasActions = false;

    /**
     * Display mode for per-row actions column.
     * DROPDOWN – 3-dot button opens a dropdown list.
     * ICONS    – all action icons shown inline with tooltips.
     */
    protected RowActionsMode $rowActionsMode = RowActionsMode::DROPDOWN;

    /** User-set column widths in pixels, keyed by column key. Persisted with state. */
    public array $columnWidths = [];

    /** Enable CSV export button. */
    protected bool $exportCsv = true;

    /** Enable PDF export button (skeleton – implement generatePdf()). */
    protected bool $exportPdf = false;

    /**
     * FQCN of the Eloquent model managed by this table (e.g. App\Models\User).
     * Required when $defaultCreating = true.
     * null (or '') means no model is configured.
     */
    protected ?string $model = null;

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
     * When true, a "Edit selected" button appears in the top-left zone.
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
     * When true (default), all bulk actions and mass delete require confirmation
     * via the accept modal before executing.
     * Set to false to execute mass actions immediately without a confirmation prompt.
     */
    protected bool $massActionRequiresConfirmation = true;

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
     * SVG icon used for the default "Edytuj" row action.
     * Override in your table class to customise the icon.
     */
    protected string $defaultEditIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';

    /**
     * SVG icon used for the default "Usuń" row action.
     * Override in your table class to customise the icon.
     */
    protected string $defaultDeleteIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';

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
     *
     * ⚠️ SECURITY — authorization scope is REQUIRED here.
     * All per-row operations (edit, delete, updateCell) and mass operations (massEdit,
     * massDelete, export) resolve records exclusively through this query. If you do not
     * constrain it to the current user's data, any authenticated user who can reach this
     * Livewire component will be able to read, modify or delete records belonging to
     * other users (IDOR — Insecure Direct Object Reference).
     *
     * Always scope the query to the authenticated user's data:
     *
     *   protected function baseQuery(): Builder
     *   {
     *       return Order::where('user_id', auth()->id());
     *   }
     *
     * Or use a global scope / policy gate inside the query:
     *
     *   protected function baseQuery(): Builder
     *   {
     *       return Order::query()->tap(function (Builder $q) {
     *           Gate::authorize('viewAny', Order::class);
     *       });
     *   }
     *
     * Override applySearch() and applyFilters() to add further logic.
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

    /**
     * Called once before the per-row subRows() loop, with all current page items.
     * Override to eager-load relations in batch and prevent N+1 queries.
     *
     * Example:
     *   $items->load('projects.tasks');
     *
     * @param \Illuminate\Support\Collection<int, mixed> $items
     */
    protected function preloadSubRows(\Illuminate\Support\Collection $items): void
    {
    }

    /**
     * Row actions that are identical for every row (computed once, not per-row).
     * Use this when your actions do not depend on row data or permissions.
     * For per-row logic use rowActions(mixed $row) instead.
     *
     * @return RowAction[]
     */
    protected function staticRowActions(): array
    {
        return [];
    }

    /**
     * Per-sub-row action buttons rendered in the sub-row actions column.
     * Requires $subRowsHasActions = true.
     *
     * @return RowAction[]
     */
    public function subRowActions(mixed $subRow): array
    {
        return [];
    }

    /**
     * Toggle selection of a sub-row by its primary key.
     */
    public function toggleSelectSubRow(string $subRowId): void
    {
        if (in_array($subRowId, $this->selectedSubRows, true)) {
            $this->selectedSubRows = array_values(
                array_filter($this->selectedSubRows, fn(string $s) => $s !== $subRowId),
            );
        } else {
            $this->selectedSubRows[] = $subRowId;
        }
    }

    // -------------------------------------------------------------------------
    // Model helper
    // -------------------------------------------------------------------------

    /**
     * Returns true when $model is a non-empty string pointing to an existing class.
     * Checks for both null and '' so that assigning either value means "no model".
     */
    private function hasModel(): bool
    {
        return $this->model !== null
            && $this->model !== ''
            && class_exists($this->model);
    }

    // -------------------------------------------------------------------------
    // Authorization hook
    // -------------------------------------------------------------------------

    /**
     * Override to add authorization checks before any write/delete/export action.
     * Throw \Illuminate\Auth\Access\AuthorizationException (or any exception) to abort.
     *
     * Called automatically before:
     *   - 'create'     → createRecord()          — $record is null
     *   - 'update'     → updateRecord()           — $record is the loaded Eloquent model
     *   - 'delete'     → deleteRow()              — $record is the loaded Eloquent model
     *   - 'massEdit'   → massEditUpdate()         — $record is null
     *   - 'massDelete' → massDelete()             — $record is null
     *   - 'export'     → exportCsv() / exportPdf() — $record is null
     *
     * Example using Laravel Gates:
     *
     *   protected function authorizeAction(string $action, mixed $record = null): void
     *   {
     *       match ($action) {
     *           'create'     => Gate::authorize('create', Order::class),
     *           'update',
     *           'delete'     => Gate::authorize($action, $record),
     *           'massEdit',
     *           'massDelete' => Gate::authorize('massManage', Order::class),
     *           'export'     => Gate::authorize('export', Order::class),
     *           default      => null,
     *       };
     *   }
     *
     * @param string $action 'create'|'update'|'delete'|'massEdit'|'massDelete'|'export'
     * @param mixed $record Eloquent model instance, or null for non-row-specific actions
     */
    protected function authorizeAction(string $action, mixed $record = null): void
    {
    }

    // -------------------------------------------------------------------------
    // Search / Filter hooks
    // -------------------------------------------------------------------------

    /**
     * Apply full-text search to the query. Called only when $search is non-empty.
     *
     * Default implementation searches all declared column keys with LIKE.
     * Override in your table class to customise the search logic.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        $columns = array_column($this->cachedColumns(), 'key');

        if (empty($columns)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns): void {
            foreach ($columns as $col) {
                $q->orWhere($col, 'like', '%' . $search . '%');
            }
        });
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
    // Password hashing helper (used by ManagesCreating and ManagesMassActions)
    // -------------------------------------------------------------------------

    /**
     * Hash fields whose key matches 'password' or '*_password' before persistence.
     * Skips empty values and respects the $autoHashPasswords flag.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function hashPasswordFields(array $data): array
    {
        if (!$this->autoHashPasswords) {
            return $data;
        }

        foreach (array_keys($data) as $key) {
            if ($data[$key] !== '' && $data[$key] !== null && Str::is(['password', '*_password'], $key)) {
                $data[$key] = Hash::make((string)$data[$key]);
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
            array_filter($cols, fn(Column $c) => !$c->visible),
            'key',
        ));

        $this->loadState();

        if ($this->perPage === 0 && $this->allowInfiniteScroll) {
            $this->loadedRows = $this->infiniteChunkSize;
        } elseif ($this->perPage === 0 && !$this->allowInfiniteScroll) {
            $this->perPage = 25;
            $this->loadedRows = 0;
        }
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
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

    /**
     * Update a single editable cell value (checkbox, select).
     * Called from the Blade view via wire:change on editable cell widgets.
     *
     * ⚠️ $rowId comes directly from the frontend. Record access is restricted by
     * baseQuery() — see its docblock for the mandatory authorization scope.
     *
     * To add per-cell authorization, override this method in your table class:
     *
     *   public function updateCell(string $rowId, string $columnKey, mixed $value): void
     *   {
     *       Gate::authorize('update', $this->baseQuery()->findOrFail($rowId));
     *       parent::updateCell($rowId, $columnKey, $value);
     *   }
     */
    /**
     * Persist the user-set width (in pixels) for a single column.
     * Validates the key against known columns to prevent arbitrary state injection.
     */
    public function saveColumnWidth(string $key, int $width): void
    {
        $keys = array_column($this->cachedColumns(), 'key');

        if (! in_array($key, $keys, true)) {
            return;
        }

        $this->columnWidths[$key] = max(50, $width);
        $this->saveState();
    }

    public function updateCell(string $rowId, string $columnKey, mixed $value): void
    {
        $col = collect($this->cachedColumns())->firstWhere('key', $columnKey);

        if ($col === null) {
            return;
        }

        $cell = $col->getCell();

        if (!($cell instanceof EditableCellInterface)) {
            return;
        }

        $cell->validate($value);

        $row = $this->baseQuery()->where($this->primaryKey, $rowId)->firstOrFail();
        $cell->update($row, $value);
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    protected function viewName(): string
    {
        return 'live-table::base-table';
    }

    /** @codeCoverageIgnore – requires full Livewire + Blade view stack */
    public function render(): mixed
    {
        $query = $this->buildQuery();
        $total = $query->count();

        $this->applySorting($query);

        $page = $this->paginateQuery($query, $total);
        $items = $page['items'];
        $visibleColumns = $this->visibleColumns();

        [$rawSumData, $countData] = $this->computeAggregates($items);
        $sumData = array_map(
            static fn(mixed $v) => is_float($v)
                ? number_format($v, 2, ',', ' ')
                : number_format((int)$v, 0, ',', ' '),
            $rawSumData,
        );

        $selectMode = $this->selectMode ?? SelectMode::CHECKBOX;
        $hasCheckboxCol = $this->selectable && $selectMode === SelectMode::CHECKBOX;
        $isRowSelectMode = $this->selectable && $selectMode === SelectMode::ROW;

        ['subRowsMap' => $subRowsMap, 'subRowActionsMap' => $subRowActionsMap]
            = $this->buildSubRowData($items);

        $canEdit = $this->defaultActions && $this->defaultActionEdit && $this->hasModel();
        $canDelete = $this->defaultActions && $this->defaultActionDelete;

        ['rowActionsMap' => $rowActionsMap, 'hasRowActions' => $hasRowActions]
            = $this->buildRowActionsData($items, $canEdit, $canDelete);

        $colspan = count($visibleColumns) + ($hasCheckboxCol ? 1 : 0) + ($this->expandable ? 1 : 0) + ($hasRowActions ? 1 : 0);
        $currentPageIds = $items->pluck($this->primaryKey)->map('strval')->all();
        $allPageSelected = !empty($currentPageIds)
            && count(array_diff($currentPageIds, $this->selected)) === 0;

        $canCreate = $this->defaultCreating && $this->hasModel();

        // creatingFields() queries the DB schema — call it once and reuse everywhere.
        // Editing and mass-edit modals intentionally share the same field definitions.
        $allCreatingFields = ($canCreate || $canEdit || $this->massEdit)
            ? $this->creatingFields()
            : [];

        $creatingFields = $canCreate ? $allCreatingFields : [];
        $editingFields = $canEdit ? $allCreatingFields : [];

        return view($this->viewName(), [
            'items' => $items,
            'total' => $total,
            'lastPage' => $page['lastPage'],
            'from' => $page['from'],
            'to' => $page['to'],
            'pages' => $page['pages'],
            'visibleColumns' => $visibleColumns,
            'allColumns' => $this->resolvedColumns(),
            'filterDefs' => $this->filters(),
            'bulkActionDefs' => $this->bulkActions(),
            'headerActionDefs' => $this->headerActions(),
            'selectable' => $this->selectable,
            'hasCheckboxCol' => $hasCheckboxCol,
            'isRowSelectMode' => $isRowSelectMode,
            'primaryKey' => $this->primaryKey,
            'currentPageIds' => $currentPageIds,
            'displaySearch' => $this->displaySearch,
            'displayColumnList' => $this->displayColumnList,
            'sumData' => $sumData,
            'countData' => $countData,
            'infiniteMode' => $page['infiniteMode'],
            'allLoaded' => $page['allLoaded'],
            'allowInfiniteScroll' => $this->allowInfiniteScroll,
            'colspan' => $colspan,
            'expandable' => $this->expandable,
            'subRowsMap' => $subRowsMap,
            'subRowsSelectable' => $this->subRowsSelectable,
            'subRowPrimaryKey' => $this->subRowPrimaryKey,
            'subRowActionsMap' => $subRowActionsMap,
            'allPageSelected' => $allPageSelected,
            'selectAllQuery' => $this->selectAllQuery,
            'exportCsv' => $this->exportCsv,
            'exportPdf' => $this->exportPdf,
            'massDeleteEnabled' => $this->massDelete,
            'hasRowActions' => $hasRowActions,
            'rowActionsMap' => $rowActionsMap,
            'rowActionsMode' => $this->rowActionsMode,
            'massEditEnabled' => $this->massEdit && $this->selectable
                && $this->hasModel()
                && !empty($allCreatingFields),
            'showMassEditModal' => $this->showMassEditModal,
            'massEditFields' => $this->massEdit ? $allCreatingFields : [],
            'canCreate' => $canCreate && !empty($creatingFields),
            'creatingFields' => $creatingFields,
            'showCreatingModal' => $this->showCreatingModal,
            'canEdit' => $canEdit,
            'editingFields' => $editingFields,
            'showEditingModal' => $this->showEditingModal,
            'creatingModalView' => $this->creatingModalView(),
            'editingModalView' => $this->editingModalView(),
            'massActionRequiresConfirmation' => $this->massActionRequiresConfirmation,
            'columnWidths' => $this->columnWidths,
        ]);
    }

}
