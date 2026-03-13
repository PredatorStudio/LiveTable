<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Enums\AggregateScope;

/**
 * Creates a minimal BaseTable stub with configurable sum/count columns and scope.
 */
function aggregateTable(
    array $sumColumns = [],
    array $countColumns = [],
    AggregateScope $scope = AggregateScope::ALL,
    ?Builder $mockQuery = null,
): BaseTable {
    return new class($sumColumns, $countColumns, $scope, $mockQuery) extends BaseTable
    {
        public function __construct(
            array $sum,
            array $count,
            AggregateScope $scope,
            private readonly ?Builder $mockQuery,
        ) {
            $this->sumColumns = $sum;
            $this->countColumns = $count;
            $this->aggregateScope = $scope;
        }

        protected function baseQuery(): Builder
        {
            return $this->mockQuery ?? Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [
                Column::make('price', 'Cena'),
                Column::make('qty', 'Ilość'),
                Column::make('name', 'Nazwa'),
            ];
        }
    };
}

/**
 * Calls the private computeAggregates() via reflection.
 */
function callComputeAggregates(BaseTable $table, Collection $items): array
{
    $method = new ReflectionMethod(BaseTable::class, 'computeAggregates');
    $method->setAccessible(true);

    return $method->invoke($table, $items);
}

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Default property values
// ---------------------------------------------------------------------------

it('has empty sumColumns by default', function () {
    $table = aggregateTable();
    expect((fn () => $this->sumColumns)->call($table))->toBe([]);
});

it('has empty countColumns by default', function () {
    $table = aggregateTable();
    expect((fn () => $this->countColumns)->call($table))->toBe([]);
});

it('has aggregateScope ALL by default', function () {
    $table = aggregateTable();
    expect((fn () => $this->aggregateScope)->call($table))->toBe(AggregateScope::ALL);
});

// ---------------------------------------------------------------------------
// No aggregate columns configured → empty results
// ---------------------------------------------------------------------------

it('returns empty sumData and countData when no aggregate columns configured', function () {
    $table = aggregateTable();
    [$sumData, $countData] = callComputeAggregates($table, collect());

    expect($sumData)->toBe([]);
    expect($countData)->toBe([]);
});

// ---------------------------------------------------------------------------
// scope = 'page' – aggregates over Collection
// ---------------------------------------------------------------------------

it('sums values from page items for configured sumColumns', function () {
    $items = collect([
        (object) ['price' => 10, 'qty' => 2],
        (object) ['price' => 20, 'qty' => 3],
        (object) ['price' => 30, 'qty' => 5],
    ]);

    $table = aggregateTable(sumColumns: ['price', 'qty'], scope: AggregateScope::PAGE);
    [$sumData] = callComputeAggregates($table, $items);

    expect($sumData['price'])->toBe(60);
    expect($sumData['qty'])->toBe(10);
});

it('counts non-null values from page items for configured countColumns', function () {
    $items = collect([
        (object) ['name' => 'Adam'],
        (object) ['name' => null],
        (object) ['name' => 'Ewa'],
    ]);

    $table = aggregateTable(countColumns: ['name'], scope: AggregateScope::PAGE);
    [, $countData] = callComputeAggregates($table, $items);

    expect($countData['name'])->toBe(2);
});

it('returns zero sum when all page values are null', function () {
    $items = collect([
        (object) ['price' => null],
        (object) ['price' => null],
    ]);

    $table = aggregateTable(sumColumns: ['price'], scope: AggregateScope::PAGE);
    [$sumData] = callComputeAggregates($table, $items);

    expect($sumData['price'])->toBe(0);
});

it('returns zero count when all page values are null', function () {
    $items = collect([
        (object) ['name' => null],
        (object) ['name' => null],
    ]);

    $table = aggregateTable(countColumns: ['name'], scope: AggregateScope::PAGE);
    [, $countData] = callComputeAggregates($table, $items);

    expect($countData['name'])->toBe(0);
});

// ---------------------------------------------------------------------------
// scope = 'all' – delegates to Builder
// ---------------------------------------------------------------------------

it('calls selectRaw() on the query builder for scope all (single sum)', function () {
    $row = (object) ['__sum_price' => 500];

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('selectRaw')->once()->andReturnSelf();
    $query->shouldReceive('first')->once()->andReturn($row);

    $table = aggregateTable(sumColumns: ['price'], scope: AggregateScope::ALL, mockQuery: $query);
    [$sumData] = callComputeAggregates($table, collect());

    expect($sumData['price'])->toBe(500);
});

it('handles multiple sumColumns for scope all with single selectRaw()', function () {
    $row = (object) ['__sum_price' => 100, '__sum_qty' => 25];

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('selectRaw')->once()->andReturnSelf();
    $query->shouldReceive('first')->once()->andReturn($row);

    $table = aggregateTable(sumColumns: ['price', 'qty'], scope: AggregateScope::ALL, mockQuery: $query);
    [$sumData] = callComputeAggregates($table, collect());

    expect($sumData['price'])->toBe(100);
    expect($sumData['qty'])->toBe(25);
});

it('scope all uses single selectRaw() with sum and count columns', function () {
    $row = (object) ['__sum_price' => 999, '__count_name' => 7];

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('selectRaw')->once()->andReturnSelf();
    $query->shouldReceive('first')->once()->andReturn($row);

    $table = aggregateTable(
        sumColumns: ['price'],
        countColumns: ['name'],
        scope: AggregateScope::ALL,
        mockQuery: $query,
    );
    [$sumData, $countData] = callComputeAggregates($table, collect());

    expect($sumData['price'])->toBe(999);
    expect($countData['name'])->toBe(7);
});

it('scope all returns zero when row is null', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('selectRaw')->once()->andReturnSelf();
    $query->shouldReceive('first')->once()->andReturn(null);

    $table = aggregateTable(sumColumns: ['price'], scope: AggregateScope::ALL, mockQuery: $query);
    [$sumData] = callComputeAggregates($table, collect());

    expect($sumData['price'])->toBe(0);
});

// ---------------------------------------------------------------------------
// Mixed sums and counts
// ---------------------------------------------------------------------------

it('can have both sumColumns and countColumns simultaneously on page scope', function () {
    $items = collect([
        (object) ['price' => 50, 'name' => 'A'],
        (object) ['price' => 50, 'name' => null],
    ]);

    $table = aggregateTable(
        sumColumns: ['price'],
        countColumns: ['name'],
        scope: AggregateScope::PAGE,
    );
    [$sumData, $countData] = callComputeAggregates($table, $items);

    expect($sumData['price'])->toBe(100);
    expect($countData['name'])->toBe(1);
});

it('does not include unconfigured columns in aggregate results', function () {
    $items = collect([
        (object) ['price' => 10, 'qty' => 2, 'name' => 'X'],
    ]);

    $table = aggregateTable(sumColumns: ['price'], scope: AggregateScope::PAGE);
    [$sumData, $countData] = callComputeAggregates($table, $items);

    expect($sumData)->toHaveKey('price');
    expect($sumData)->not->toHaveKey('qty');
    expect($sumData)->not->toHaveKey('name');
    expect($countData)->toBe([]);
});
