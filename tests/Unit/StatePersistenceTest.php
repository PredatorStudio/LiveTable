<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Contracts\TableStateRepositoryInterface;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helper – minimal table stub
// ---------------------------------------------------------------------------

function makeStatePersistenceTable(string $tableId = '', bool $persistState = true): BaseTable
{
    return new class($tableId, $persistState) extends BaseTable
    {
        public function __construct(string $tid, bool $ps)
        {
            $this->tableId = $tid;
            $this->persistState = $ps;
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
}

function callGetTableIdentifier(BaseTable $table): string
{
    $method = new ReflectionMethod($table, 'getTableIdentifier');
    $method->setAccessible(true);

    return $method->invoke($table);
}

function callResolveClientIdentifier(BaseTable $table): array
{
    $method = new ReflectionMethod($table, 'resolveClientIdentifier');
    $method->setAccessible(true);

    return $method->invoke($table);
}

// ---------------------------------------------------------------------------
// getTableIdentifier()
// ---------------------------------------------------------------------------

it('getTableIdentifier returns tableId when tableId is set', function () {
    $table = makeStatePersistenceTable(tableId: 'my-custom-table');

    expect(callGetTableIdentifier($table))->toBe('my-custom-table');
});

it('getTableIdentifier returns class name when tableId is empty', function () {
    $table = makeStatePersistenceTable(tableId: '');
    $className = get_class($table);

    expect(callGetTableIdentifier($table))->toBe($className);
});

it('getTableIdentifier returns FQCN (not short class name)', function () {
    $table = makeStatePersistenceTable(tableId: '');
    $identifier = callGetTableIdentifier($table);

    expect($identifier)->toContain('\\');
});

it('different table instances with same tableId get same identifier', function () {
    $table1 = makeStatePersistenceTable(tableId: 'orders-table');
    $table2 = makeStatePersistenceTable(tableId: 'orders-table');

    expect(callGetTableIdentifier($table1))->toBe(callGetTableIdentifier($table2));
});

// ---------------------------------------------------------------------------
// resolveClientIdentifier() – authenticated user
// ---------------------------------------------------------------------------

it('resolveClientIdentifier returns user_id when authenticated', function () {
    // Mock auth to return authenticated user
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(42);

    $table = makeStatePersistenceTable();
    $identifier = callResolveClientIdentifier($table);

    expect($identifier['user_id'])->toBe(42)
        ->and($identifier['client_id'])->toBeNull();
});

// ---------------------------------------------------------------------------
// resolveClientIdentifier() – guest user (session-based)
// ---------------------------------------------------------------------------

it('resolveClientIdentifier returns client_id from session when not authenticated', function () {
    Auth::shouldReceive('check')->andReturn(false);
    session(['live_table_client_id' => 'existing-uuid-123']);

    $table = makeStatePersistenceTable();
    $identifier = callResolveClientIdentifier($table);

    expect($identifier['user_id'])->toBeNull()
        ->and($identifier['client_id'])->toBe('existing-uuid-123');
});

it('resolveClientIdentifier creates new UUID when no session client_id exists', function () {
    Auth::shouldReceive('check')->andReturn(false);
    // Ensure no existing session key
    session()->forget('live_table_client_id');

    $table = makeStatePersistenceTable();
    $identifier = callResolveClientIdentifier($table);

    expect($identifier['client_id'])->not->toBeNull();
    expect(Str::isUuid($identifier['client_id']))->toBeTrue();
});

it('resolveClientIdentifier stores new UUID in session', function () {
    Auth::shouldReceive('check')->andReturn(false);
    session()->forget('live_table_client_id');

    $table = makeStatePersistenceTable();
    callResolveClientIdentifier($table);

    expect(session('live_table_client_id'))->not->toBeNull();
    expect(Str::isUuid(session('live_table_client_id')))->toBeTrue();
});

it('resolveClientIdentifier returns same client_id on consecutive calls when not authenticated', function () {
    Auth::shouldReceive('check')->andReturn(false);
    session()->forget('live_table_client_id');

    $table = makeStatePersistenceTable();
    $first = callResolveClientIdentifier($table);
    $second = callResolveClientIdentifier($table);

    expect($first['client_id'])->toBe($second['client_id']);
});

// ---------------------------------------------------------------------------
// saveState() – only runs when persistState = true
// ---------------------------------------------------------------------------

it('saveState does nothing when persistState is false', function () {
    $repo = Mockery::mock(TableStateRepositoryInterface::class);
    $repo->shouldReceive('save')->never();
    app()->instance(TableStateRepositoryInterface::class, $repo);

    $table = makeStatePersistenceTable(persistState: false);
    $table->saveState();
});

it('saveState calls repository save when persistState is true', function () {
    Auth::shouldReceive('check')->andReturn(false);
    session()->forget('live_table_client_id');
    session(['live_table_client_id' => 'test-client-id']);

    $repo = Mockery::mock(TableStateRepositoryInterface::class);
    $repo->shouldReceive('save')->once();
    app()->instance(TableStateRepositoryInterface::class, $repo);

    $table = makeStatePersistenceTable(persistState: true);
    $table->saveState();
});

it('saveState includes current search in persisted state', function () {
    Auth::shouldReceive('check')->andReturn(false);
    session(['live_table_client_id' => 'test-client']);

    $capturedState = null;
    $repo = Mockery::mock(TableStateRepositoryInterface::class);
    $repo->shouldReceive('save')
        ->once()
        ->with(Mockery::any(), Mockery::any(), Mockery::on(function ($state) use (&$capturedState) {
            $capturedState = $state;

            return true;
        }));
    app()->instance(TableStateRepositoryInterface::class, $repo);

    $table = makeStatePersistenceTable(persistState: true);
    $table->search = 'hello';
    $table->saveState();

    expect($capturedState['search'])->toBe('hello');
});