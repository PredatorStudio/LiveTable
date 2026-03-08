<?php

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use PredatorStudio\LiveTable\Action;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\BulkAction;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Filter;

// ---------------------------------------------------------------------------
// Helper – minimal BaseTable stub (skips Livewire constructor)
// ---------------------------------------------------------------------------

function stubTable(array $columns = []): BaseTable
{
    if (empty($columns)) {
        $columns = [
            Column::make('name', 'Nazwa')->sortable(),
            Column::make('email', 'Email'),
            Column::make('hidden_col', 'Ukryta')->hidden(),
        ];
    }

    return new class ($columns) extends BaseTable {
        public function __construct(private readonly array $cols)
        {
            // Skip Livewire constructor intentionally
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return $this->cols;
        }
    };
}

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// mount()
// ---------------------------------------------------------------------------

it('mount initializes columnOrder from columns', function () {
    $table = stubTable();
    $table->mount();

    expect($table->columnOrder)->toBe(['name', 'email', 'hidden_col']);
});

it('mount sets hiddenColumns for columns marked hidden()', function () {
    $table = stubTable();
    $table->mount();

    expect($table->hiddenColumns)->toBe(['hidden_col']);
});

it('mount resets columnsCache for fresh initialization', function () {
    $table = stubTable();
    $table->mount();
    $table->mount(); // second call should not throw

    expect($table->columnOrder)->toBe(['name', 'email', 'hidden_col']);
});

// ---------------------------------------------------------------------------
// updatedSearch() / updatedPerPage()
// ---------------------------------------------------------------------------

it('updatedSearch resets page to 1', function () {
    $table       = stubTable();
    $table->page = 5;
    $table->updatedSearch();

    expect($table->page)->toBe(1);
});

it('updatedPerPage resets page to 1', function () {
    $table       = stubTable();
    $table->page = 3;
    $table->updatedPerPage();

    expect($table->page)->toBe(1);
});

// ---------------------------------------------------------------------------
// sort()
// ---------------------------------------------------------------------------

it('sort sets sortBy and resets to asc on first click', function () {
    $table = stubTable();
    $table->sort('name');

    expect($table->sortBy)->toBe('name');
    expect($table->sortDir)->toBe('asc');
    expect($table->page)->toBe(1);
});

it('sort toggles direction to desc on second click', function () {
    $table         = stubTable();
    $table->sortBy = 'name';
    $table->sort('name');

    expect($table->sortDir)->toBe('desc');
});

it('sort toggles back to asc from desc', function () {
    $table          = stubTable();
    $table->sortBy  = 'name';
    $table->sortDir = 'desc';
    $table->sort('name');

    expect($table->sortDir)->toBe('asc');
});

it('sort ignores non-sortable column', function () {
    $table = stubTable();
    $table->sort('email'); // not sortable

    expect($table->sortBy)->toBe('');
});

// ---------------------------------------------------------------------------
// setPage()
// ---------------------------------------------------------------------------

it('setPage sets the current page', function () {
    $table = stubTable();
    $table->setPage(3);

    expect($table->page)->toBe(3);
});

it('setPage enforces minimum of 1', function () {
    $table = stubTable();
    $table->setPage(0);

    expect($table->page)->toBe(1);
});

// ---------------------------------------------------------------------------
// toggleColumn()
// ---------------------------------------------------------------------------

it('toggleColumn hides a visible column', function () {
    $table = stubTable();
    $table->mount();
    $table->toggleColumn('name');

    expect($table->hiddenColumns)->toContain('name');
});

it('toggleColumn shows a hidden column', function () {
    $table = stubTable();
    $table->mount();
    $table->toggleColumn('hidden_col');

    expect($table->hiddenColumns)->not->toContain('hidden_col');
});

it('toggleColumn ignores unknown column key', function () {
    $table = stubTable();
    $table->mount();
    $before = $table->hiddenColumns;
    $table->toggleColumn('nonexistent');

    expect($table->hiddenColumns)->toBe($before);
});

// ---------------------------------------------------------------------------
// reorderColumns()
// ---------------------------------------------------------------------------

it('reorderColumns applies valid order', function () {
    $table = stubTable();
    $table->mount();
    $table->reorderColumns(['email', 'name', 'hidden_col']);

    expect($table->columnOrder)->toBe(['email', 'name', 'hidden_col']);
});

it('reorderColumns appends missing columns at end', function () {
    $table = stubTable();
    $table->mount();
    $table->reorderColumns(['email', 'name']); // hidden_col missing

    expect($table->columnOrder[2])->toBe('hidden_col');
});

it('reorderColumns ignores unknown keys', function () {
    $table = stubTable();
    $table->mount();
    $table->reorderColumns(['nonexistent', 'name', 'email', 'hidden_col']);

    expect($table->columnOrder)->not->toContain('nonexistent');
});

// ---------------------------------------------------------------------------
// applyActiveFilters() / clearFilters()
// ---------------------------------------------------------------------------

it('applyActiveFilters closes modal and resets page', function () {
    $table                   = stubTable();
    $table->showFiltersModal = true;
    $table->page             = 4;
    $table->applyActiveFilters();

    expect($table->showFiltersModal)->toBeFalse();
    expect($table->page)->toBe(1);
});

it('clearFilters empties activeFilters and closes modal', function () {
    $table                   = stubTable();
    $table->activeFilters    = ['status' => 'active'];
    $table->showFiltersModal = true;
    $table->page             = 2;
    $table->clearFilters();

    expect($table->activeFilters)->toBe([]);
    expect($table->showFiltersModal)->toBeFalse();
    expect($table->page)->toBe(1);
});

// ---------------------------------------------------------------------------
// removeFilter()
// ---------------------------------------------------------------------------

it('removeFilter removes specified key from activeFilters', function () {
    $table               = stubTable();
    $table->activeFilters = ['status' => 'active', 'role' => 'admin'];
    $table->removeFilter('status');

    expect($table->activeFilters)->not->toHaveKey('status');
    expect($table->activeFilters)->toHaveKey('role');
});

it('removeFilter resets page to 1', function () {
    $table               = stubTable();
    $table->activeFilters = ['status' => 'active'];
    $table->page         = 4;
    $table->removeFilter('status');

    expect($table->page)->toBe(1);
});

it('removeFilter does nothing when key does not exist', function () {
    $table               = stubTable();
    $table->activeFilters = ['role' => 'admin'];
    $table->removeFilter('nonexistent');

    expect($table->activeFilters)->toBe(['role' => 'admin']);
});

it('removeFilter leaves other filters intact', function () {
    $table               = stubTable();
    $table->activeFilters = ['a' => '1', 'b' => '2', 'c' => '3'];
    $table->removeFilter('b');

    expect($table->activeFilters)->toBe(['a' => '1', 'c' => '3']);
});

// ---------------------------------------------------------------------------
// toggleSelectRow() / selectRows() / deselectRows()
// ---------------------------------------------------------------------------

it('toggleSelectRow adds row id to selected', function () {
    $table = stubTable();
    $table->toggleSelectRow('5');

    expect($table->selected)->toContain('5');
});

it('toggleSelectRow removes already-selected row', function () {
    $table           = stubTable();
    $table->selected = ['5'];
    $table->toggleSelectRow('5');

    expect($table->selected)->not->toContain('5');
});

it('selectRows adds multiple ids', function () {
    $table = stubTable();
    $table->selectRows(['1', '2', '3']);

    expect($table->selected)->toEqualCanonicalizing(['1', '2', '3']);
});

it('selectRows deduplicates', function () {
    $table           = stubTable();
    $table->selected = ['1'];
    $table->selectRows(['1', '2']);

    expect($table->selected)->toHaveCount(2);
});

it('deselectRows removes specified ids', function () {
    $table           = stubTable();
    $table->selected = ['1', '2', '3'];
    $table->deselectRows(['2']);

    expect($table->selected)->not->toContain('2');
    expect($table->selected)->toContain('1');
    expect($table->selected)->toContain('3');
});

// ---------------------------------------------------------------------------
// Default hook method implementations
// ---------------------------------------------------------------------------

it('filters() returns empty array by default', function () {
    $table = stubTable();
    expect($table->filters())->toBe([]);
});

it('bulkActions() returns empty array by default', function () {
    $table = stubTable();
    expect($table->bulkActions())->toBe([]);
});

it('headerActions() returns empty array by default', function () {
    $table = stubTable();
    expect($table->headerActions())->toBe([]);
});

it('applySearch returns query unchanged by default', function () {
    $table        = stubTable();
    $mockBuilder  = Mockery::mock(Builder::class);
    $reflection   = new ReflectionMethod($table, 'applySearch');

    $result = $reflection->invoke($table, $mockBuilder, 'test');

    expect($result)->toBe($mockBuilder);
});

it('applyFilters returns query unchanged by default', function () {
    $table       = stubTable();
    $mockBuilder = Mockery::mock(Builder::class);
    $reflection  = new ReflectionMethod($table, 'applyFilters');

    $result = $reflection->invoke($table, $mockBuilder);

    expect($result)->toBe($mockBuilder);
});

// ---------------------------------------------------------------------------
// buildPageLinks() – private, tested via Reflection
// ---------------------------------------------------------------------------

it('buildPageLinks returns empty array for single page', function () {
    $table      = stubTable();
    $reflection = new ReflectionMethod($table, 'buildPageLinks');

    expect($reflection->invoke($table, 1))->toBe([]);
});

it('buildPageLinks includes first and last page', function () {
    $table        = stubTable();
    $table->page  = 1;
    $reflection   = new ReflectionMethod($table, 'buildPageLinks');

    $links = $reflection->invoke($table, 10);

    expect($links)->toContain(1);
    expect($links)->toContain(10);
});

it('buildPageLinks adds ellipsis for gaps', function () {
    $table        = stubTable();
    $table->page  = 1;
    $reflection   = new ReflectionMethod($table, 'buildPageLinks');

    $links = $reflection->invoke($table, 20);

    expect($links)->toContain('...');
});

it('buildPageLinks includes pages around current page', function () {
    $table        = stubTable();
    $table->page  = 10;
    $reflection   = new ReflectionMethod($table, 'buildPageLinks');

    $links = $reflection->invoke($table, 20);

    expect($links)->toContain(8);
    expect($links)->toContain(9);
    expect($links)->toContain(10);
    expect($links)->toContain(11);
    expect($links)->toContain(12);
});

// ---------------------------------------------------------------------------
// resolvedColumns() / visibleColumns() – private, tested via Reflection
// ---------------------------------------------------------------------------

it('resolvedColumns returns columns in columnOrder sequence', function () {
    $table = stubTable();
    $table->mount();
    $table->reorderColumns(['email', 'name', 'hidden_col']);

    $reflection = new ReflectionMethod($table, 'resolvedColumns');
    $cols       = $reflection->invoke($table);

    expect($cols[0]->key)->toBe('email');
    expect($cols[1]->key)->toBe('name');
});

it('visibleColumns excludes hidden columns', function () {
    $table = stubTable();
    $table->mount(); // hidden_col is hidden by default

    $reflection = new ReflectionMethod($table, 'visibleColumns');
    $cols       = $reflection->invoke($table);
    $keys       = array_column($cols, 'key');

    expect($keys)->not->toContain('hidden_col');
    expect($keys)->toContain('name');
    expect($keys)->toContain('email');
});

it('visibleColumns includes all columns when none hidden', function () {
    $table = stubTable([
        Column::make('a', 'A'),
        Column::make('b', 'B'),
    ]);
    $table->mount();

    $reflection = new ReflectionMethod($table, 'visibleColumns');
    $cols       = $reflection->invoke($table);

    expect($cols)->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// buildQuery() – private, tested via Reflection
// ---------------------------------------------------------------------------

it('buildQuery returns base query when search is empty', function () {
    $mockBuilder = Mockery::mock(Builder::class);

    $table = new class ($mockBuilder) extends BaseTable {
        public function __construct(private readonly mixed $builder)
        {
            // Skip Livewire constructor
        }

        protected function baseQuery(): Builder { return $this->builder; }

        public function columns(): array { return [Column::make('name', 'Nazwa')]; }
    };

    $reflection = new ReflectionMethod($table, 'buildQuery');
    $result     = $reflection->invoke($table);

    expect($result)->toBe($mockBuilder);
});

it('buildQuery calls applySearch when search is set', function () {
    $searchApplied = false;
    $mockBuilder   = Mockery::mock(Builder::class);

    $table = new class ($mockBuilder, $searchApplied) extends BaseTable {
        public function __construct(
            private readonly mixed $builder,
            private bool &$searchApplied,
        ) {
            // Skip Livewire constructor
        }

        protected function baseQuery(): Builder { return $this->builder; }

        public function columns(): array { return [Column::make('name', 'Nazwa')]; }

        protected function applySearch(Builder $query, string $search): Builder
        {
            $this->searchApplied = true;

            return $query;
        }
    };

    $table->search = 'test';

    $reflection = new ReflectionMethod($table, 'buildQuery');
    $reflection->invoke($table);

    expect($searchApplied)->toBeTrue();
});

it('resolvedColumns appends column not present in columnOrder', function () {
    $table = stubTable([
        Column::make('a', 'A'),
        Column::make('b', 'B'),
    ]);
    $table->mount();
    // Simulate columnOrder missing 'b' (e.g. newly added column)
    $table->columnOrder = ['a'];

    $reflection = new ReflectionMethod($table, 'resolvedColumns');
    $cols       = $reflection->invoke($table);
    $keys       = array_column($cols, 'key');

    expect($keys)->toContain('a');
    expect($keys)->toContain('b'); // appended at end
    expect($keys[0])->toBe('a');
    expect($keys[1])->toBe('b');
});
