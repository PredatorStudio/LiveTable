<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use PredatorStudio\LiveTable\Models\TableState;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);

    Schema::create('live_table_states', function ($table) {
        $table->id();
        $table->string('table_id', 191);
        $table->unsignedBigInteger('user_id')->nullable()->index();
        $table->string('client_id', 36)->nullable()->index();
        $table->json('state');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('live_table_states');
    Mockery::close();
});

// ---------------------------------------------------------------------------
// Helper – minimal persisting BaseTable stub
// ---------------------------------------------------------------------------

function makePersistTable(bool $persist = true, string $tableId = 'TestTable'): BaseTable
{
    return new class($persist, $tableId) extends BaseTable
    {
        public bool $persistState = false;

        public string $tableId = '';

        public function __construct(bool $persist, string $tid)
        {
            // Skip Livewire constructor
            $this->persistState = $persist;
            $this->tableId = $tid;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [
                Column::make('name', 'Nazwa')->sortable(),
                Column::make('email', 'Email'),
            ];
        }
    };
}

// ---------------------------------------------------------------------------
// Tests: persistState = false (default) – no DB interaction
// ---------------------------------------------------------------------------

it('does not persist when persistState is false', function () {
    $table = makePersistTable(persist: false);
    $table->mount();

    $table->saveState();

    expect(TableState::count())->toBe(0);
});

it('does not load state when persistState is false', function () {
    TableState::create([
        'table_id' => 'TestTable',
        'user_id' => null,
        'client_id' => 'some-uuid',
        'state' => ['search' => 'ignored'],
    ]);

    $table = makePersistTable(persist: false);
    $table->search = '';
    $table->mount();

    expect($table->search)->toBe('');
});

// ---------------------------------------------------------------------------
// Tests: saveState() writes to DB
// ---------------------------------------------------------------------------

it('saves state to database for guest user', function () {
    $clientId = 'test-client-uuid';
    session(['live_table_client_id' => $clientId]);

    $table = makePersistTable();
    $table->search = 'foo';
    $table->activeFilters = ['status' => 'active'];
    $table->perPage = 50;
    $table->sortBy = 'name';
    $table->sortDir = 'desc';
    $table->columnOrder = ['name', 'email'];
    $table->hiddenColumns = ['email'];

    $table->saveState();

    $record = TableState::first();

    expect($record)->not->toBeNull()
        ->and($record->table_id)->toBe('TestTable')
        ->and($record->user_id)->toBeNull()
        ->and($record->client_id)->toBe($clientId)
        ->and($record->state['search'])->toBe('foo')
        ->and($record->state['active_filters'])->toBe(['status' => 'active'])
        ->and($record->state['per_page'])->toBe(50)
        ->and($record->state['sort_by'])->toBe('name')
        ->and($record->state['sort_dir'])->toBe('desc')
        ->and($record->state['column_order'])->toBe(['name', 'email'])
        ->and($record->state['hidden_columns'])->toBe(['email']);
});

it('updates existing state on subsequent save', function () {
    $clientId = 'test-client-uuid';
    session(['live_table_client_id' => $clientId]);

    $table = makePersistTable();
    $table->search = 'first';
    $table->saveState();

    $table->search = 'second';
    $table->saveState();

    expect(TableState::count())->toBe(1)
        ->and(TableState::first()->state['search'])->toBe('second');
});

it('generates client_id if not in session and stores it', function () {
    $table = makePersistTable();
    $table->saveState();

    $clientId = session('live_table_client_id');
    expect($clientId)->not->toBeNull()->not->toBeEmpty();

    $record = TableState::first();
    expect($record->client_id)->toBe($clientId);
});

// ---------------------------------------------------------------------------
// Tests: loadState() reads from DB
// ---------------------------------------------------------------------------

it('loads saved state on mount', function () {
    $clientId = 'load-test-uuid';
    session(['live_table_client_id' => $clientId]);

    TableState::create([
        'table_id' => 'TestTable',
        'user_id' => null,
        'client_id' => $clientId,
        'state' => [
            'search' => 'loaded',
            'active_filters' => ['role' => 'admin'],
            'per_page' => 100,
            'sort_by' => 'email',
            'sort_dir' => 'desc',
            'column_order' => ['email', 'name'],
            'hidden_columns' => ['name'],
        ],
    ]);

    $table = makePersistTable();
    $table->mount();

    expect($table->search)->toBe('loaded')
        ->and($table->activeFilters)->toBe(['role' => 'admin'])
        ->and($table->perPage)->toBe(100)
        ->and($table->sortBy)->toBe('email')
        ->and($table->sortDir)->toBe('desc')
        ->and($table->columnOrder)->toBe(['email', 'name'])
        ->and($table->hiddenColumns)->toBe(['name']);
});

it('keeps defaults when no saved state exists', function () {
    $table = makePersistTable();
    $table->mount();

    expect($table->search)->toBe('')
        ->and($table->perPage)->toBe(25)
        ->and($table->sortBy)->toBe('')
        ->and($table->sortDir)->toBe('asc');
});

// ---------------------------------------------------------------------------
// Tests: table identifier isolation
// ---------------------------------------------------------------------------

it('saves state under the correct table_id', function () {
    session(['live_table_client_id' => 'uuid-1']);

    $tableA = makePersistTable(tableId: 'TableA');
    $tableA->search = 'alpha';
    $tableA->saveState();

    $tableB = makePersistTable(tableId: 'TableB');
    $tableB->search = 'beta';
    $tableB->saveState();

    expect(TableState::count())->toBe(2)
        ->and(TableState::where('table_id', 'TableA')->first()->state['search'])->toBe('alpha')
        ->and(TableState::where('table_id', 'TableB')->first()->state['search'])->toBe('beta');
});

it('uses class name as default table_id when tableId is empty', function () {
    session(['live_table_client_id' => 'uuid-x']);

    $table = new class extends BaseTable
    {
        public bool $persistState = true;

        public function __construct()
        {
            // Skip Livewire constructor
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };

    $table->saveState();

    $record = TableState::first();
    expect($record->table_id)->toBe($table::class);
});

// ---------------------------------------------------------------------------
// Tests: saveState() is called after state-mutating actions
// ---------------------------------------------------------------------------

it('saves state after sort()', function () {
    session(['live_table_client_id' => 'uuid-sort']);

    $table = makePersistTable();
    $table->mount();
    $table->sort('name');

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['sort_by'])->toBe('name')
        ->and($record->state['sort_dir'])->toBe('asc');
});

it('saves state after toggleColumn()', function () {
    session(['live_table_client_id' => 'uuid-toggle']);

    $table = makePersistTable();
    $table->mount();
    $table->toggleColumn('email');

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['hidden_columns'])->toContain('email');
});

it('saves state after reorderColumns()', function () {
    session(['live_table_client_id' => 'uuid-reorder']);

    $table = makePersistTable();
    $table->mount();
    $table->reorderColumns(['email', 'name']);

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['column_order'])->toBe(['email', 'name']);
});

it('saves state after updatedSearch()', function () {
    session(['live_table_client_id' => 'uuid-search']);

    $table = makePersistTable();
    $table->search = 'test query';
    $table->mount();
    $table->updatedSearch();

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['search'])->toBe('test query');
});

it('saves state after updatedPerPage()', function () {
    session(['live_table_client_id' => 'uuid-perpage']);

    $table = makePersistTable();
    $table->perPage = 50;
    $table->mount();
    $table->updatedPerPage();

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['per_page'])->toBe(50);
});

it('saves state after applyActiveFilters()', function () {
    session(['live_table_client_id' => 'uuid-filters']);

    $table = makePersistTable();
    $table->activeFilters = ['status' => 'active'];
    $table->mount();
    $table->applyActiveFilters();

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['active_filters'])->toBe(['status' => 'active']);
});

it('saves state after clearFilters()', function () {
    session(['live_table_client_id' => 'uuid-clear']);

    $table = makePersistTable();
    $table->activeFilters = ['status' => 'active'];
    $table->mount();
    $table->clearFilters();

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['active_filters'])->toBe([]);
});

it('saves state after removeFilter()', function () {
    session(['live_table_client_id' => 'uuid-remove']);

    $table = makePersistTable();
    $table->activeFilters = ['status' => 'active', 'role' => 'admin'];
    $table->mount();
    $table->removeFilter('status');

    $record = TableState::first();
    expect($record)->not->toBeNull()
        ->and($record->state['active_filters'])->toBe(['role' => 'admin'])
        ->and(array_key_exists('status', $record->state['active_filters']))->toBeFalse();
});
