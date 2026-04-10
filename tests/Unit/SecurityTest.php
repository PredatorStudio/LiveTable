<?php

use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Stub helper
// ---------------------------------------------------------------------------

function securityStub(array $extraCols = []): BaseTable
{
    $cols = array_merge([
        Column::make('name', 'Nazwa')->sortable(),
        Column::make('email', 'Email'),
    ], $extraCols);

    return new class($cols) extends BaseTable
    {
        public function __construct(private readonly array $cols)
        {
            // Skip Livewire constructor
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

// ===========================================================================
// 1.1 – applySorting() – walidacja kolumny przed orderBy()
// ===========================================================================

function applySortingOn(BaseTable $table): Builder
{
    $query = Mockery::mock(Builder::class);
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
    return $query;
}

it('applySorting does not call orderBy when column is not sortable', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = 'email'; // email nie jest sortable

    $query = Mockery::mock(Builder::class);
    $query->shouldNotReceive('orderBy');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting does not call orderBy for SQL injection attempt', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = 'name; DROP TABLE users--';

    $query = Mockery::mock(Builder::class);
    $query->shouldNotReceive('orderBy');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting calls orderBy with correct column when sortable', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy  = 'name';
    $table->sortDir = 'asc';

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'asc');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting does not call orderBy when sortBy is empty', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = '';

    $query = Mockery::mock(Builder::class);
    $query->shouldNotReceive('orderBy');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

// ===========================================================================
// 1.2 – applySorting() – walidacja kierunku sortowania
// ===========================================================================

it('applySorting uses asc for invalid sortDir value', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy  = 'name';
    $table->sortDir = 'INVALID; DROP TABLE';

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'asc');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting uses asc when sortDir is asc', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy  = 'name';
    $table->sortDir = 'asc';

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'asc');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting uses desc when sortDir is desc', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy  = 'name';
    $table->sortDir = 'desc';

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'desc');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting uses asc for empty sortDir', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy  = 'name';
    $table->sortDir = '';

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'asc');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

it('applySorting uses asc for uppercase ASC (not accepted)', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy  = 'name';
    $table->sortDir = 'ASC';

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'asc');
    (new ReflectionMethod($table, 'applySorting'))->invoke($table, $query);
});

// ===========================================================================
// 1.3 – $creatableFields whitelist
// ===========================================================================

it('creatableFields defaults to empty array', function () {
    $table = securityStub();
    $prop = new ReflectionProperty($table, 'creatableFields');

    expect($prop->getValue($table))->toBe([]);
});

it('editableFields defaults to empty array', function () {
    $table = securityStub();
    $prop = new ReflectionProperty($table, 'editableFields');

    expect($prop->getValue($table))->toBe([]);
});

// ===========================================================================
// 1.4 – Limit $selected
// ===========================================================================

it('toggleSelectRow does not add when maxSelected limit is reached', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 3);

    $table->selected = ['1', '2', '3'];
    $table->toggleSelectRow('4');

    expect($table->selected)->toHaveCount(3);
    expect($table->selected)->not->toContain('4');
});

it('toggleSelectRow still removes when at limit', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 3);

    $table->selected = ['1', '2', '3'];
    $table->toggleSelectRow('1'); // usunięcie działa nawet przy limicie

    expect($table->selected)->not->toContain('1');
    expect($table->selected)->toHaveCount(2);
});

it('selectRows does not exceed maxSelected limit', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 3);

    $table->selected = ['1'];
    $table->selectRows(['2', '3', '4', '5']); // limit 3, były 1, dodajemy max 2

    expect($table->selected)->toHaveCount(3);
});

it('selectRows adds nothing when already at maxSelected limit', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 2);

    $table->selected = ['1', '2'];
    $table->selectRows(['3', '4']);

    expect($table->selected)->toHaveCount(2);
    expect($table->selected)->not->toContain('3');
});

it('maxSelected defaults to 10000', function () {
    $table = securityStub();
    $prop = new ReflectionProperty($table, 'maxSelected');

    expect($prop->getValue($table))->toBe(10_000);
});
