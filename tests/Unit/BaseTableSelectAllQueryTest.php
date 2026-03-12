<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

function makeSelectAllTable(): BaseTable
{
    return new class extends BaseTable
    {
        public function __construct()
        {
            $this->selectable = true;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };
}

// ---------------------------------------------------------------------------
// selectAllFromQuery()
// ---------------------------------------------------------------------------

it('sets selectAllQuery to true', function () {
    $table = makeSelectAllTable();

    $table->selectAllFromQuery();

    expect($table->selectAllQuery)->toBeTrue();
});

it('clearSelectAllQuery resets flag and clears selected', function () {
    $table = makeSelectAllTable();
    $table->selectAllQuery = true;
    $table->selected = ['1', '2', '3'];

    $table->clearSelectAllQuery();

    expect($table->selectAllQuery)->toBeFalse()
        ->and($table->selected)->toBe([]);
});

// ---------------------------------------------------------------------------
// Reset selectAllQuery on context change
// ---------------------------------------------------------------------------

it('resets selectAllQuery on updatedSearch', function () {
    $table = makeSelectAllTable();
    $table->selectAllQuery = true;

    $table->updatedSearch();

    expect($table->selectAllQuery)->toBeFalse();
});

it('resets selectAllQuery on sort', function () {
    $table = makeSelectAllTable();
    $table->selectAllQuery = true;

    $table->sort('name');

    expect($table->selectAllQuery)->toBeFalse();
});

it('resets selectAllQuery on applyActiveFilters', function () {
    $table = makeSelectAllTable();
    $table->selectAllQuery = true;

    $table->applyActiveFilters();

    expect($table->selectAllQuery)->toBeFalse();
});

it('resets selectAllQuery on clearFilters', function () {
    $table = makeSelectAllTable();
    $table->selectAllQuery = true;

    $table->clearFilters();

    expect($table->selectAllQuery)->toBeFalse();
});

it('resets selectAllQuery on removeFilter', function () {
    $table = makeSelectAllTable();
    $table->selectAllQuery = true;
    $table->activeFilters = ['status' => 'active'];

    $table->removeFilter('status');

    expect($table->selectAllQuery)->toBeFalse();
});

// ---------------------------------------------------------------------------
// allPageSelected passed to view
// ---------------------------------------------------------------------------

it('passes allPageSelected true when all page ids are selected', function () {
    $rows = Collection::make([
        (object) ['id' => 1],
        (object) ['id' => 2],
    ]);

    $table = new class($rows) extends BaseTable
    {
        public function __construct(private readonly Collection $rows)
        {
            $this->selected = ['1', '2'];
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(2);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn($this->rows);

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };

    $table->mount();
    $data = $table->render()->getData();

    expect($data['allPageSelected'])->toBeTrue();
});

it('passes allPageSelected false when not all page ids are selected', function () {
    $rows = Collection::make([
        (object) ['id' => 1],
        (object) ['id' => 2],
    ]);

    $table = new class($rows) extends BaseTable
    {
        public function __construct(private readonly Collection $rows)
        {
            $this->selected = ['1'];
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(2);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn($this->rows);

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };

    $table->mount();
    $data = $table->render()->getData();

    expect($data['allPageSelected'])->toBeFalse();
});
