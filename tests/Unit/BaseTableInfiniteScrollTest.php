<?php

use Illuminate\Database\Eloquent\Builder;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(\Orchestra\Testbench\TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeInfiniteTable(int $perPage = 0, bool $allow = true, int $chunkSize = 50): BaseTable
{
    return new class ($perPage, $allow, $chunkSize) extends BaseTable {
        public function __construct(int $pp, bool $allow, int $chunk)
        {
            $this->perPage             = $pp;
            $this->allowInfiniteScroll = $allow;
            $this->infiniteChunkSize   = $chunk;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')->sortable()];
        }
    };
}

// ---------------------------------------------------------------------------
// mount() initialisation
// ---------------------------------------------------------------------------

it('initializes loadedRows to chunkSize on mount when perPage is zero', function () {
    $table = makeInfiniteTable(perPage: 0, chunkSize: 30);
    $table->mount();

    expect($table->loadedRows)->toBe(30);
});

it('does not initialize loadedRows when perPage is not zero', function () {
    $table = makeInfiniteTable(perPage: 25);
    $table->mount();

    expect($table->loadedRows)->toBe(0);
});

it('falls back to default perPage when infinite not allowed and state is zero', function () {
    $table = makeInfiniteTable(perPage: 0, allow: false);
    $table->mount();

    expect($table->perPage)->toBe(25)
        ->and($table->loadedRows)->toBe(0);
});

// ---------------------------------------------------------------------------
// loadMore()
// ---------------------------------------------------------------------------

it('loadMore increases loadedRows by chunkSize', function () {
    $table              = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->loadedRows  = 50;

    $table->loadMore();

    expect($table->loadedRows)->toBe(100);
});

it('loadMore does nothing when perPage is not zero', function () {
    $table              = makeInfiniteTable(perPage: 25);
    $table->loadedRows  = 0;

    $table->loadMore();

    expect($table->loadedRows)->toBe(0);
});

it('loadMore can be called multiple times', function () {
    $table             = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->loadedRows = 50;

    $table->loadMore();
    $table->loadMore();
    $table->loadMore();

    expect($table->loadedRows)->toBe(200);
});

// ---------------------------------------------------------------------------
// Reset on state change
// ---------------------------------------------------------------------------

it('resets loadedRows on sort when in infinite mode', function () {
    $table             = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->loadedRows = 200;
    $table->mount();

    $table->sort('name');

    expect($table->loadedRows)->toBe(50);
});

it('does not reset loadedRows on sort when not in infinite mode', function () {
    $table             = makeInfiniteTable(perPage: 25);
    $table->loadedRows = 0;

    $table->sort('name');

    expect($table->loadedRows)->toBe(0);
});

it('resets loadedRows on updatedSearch when in infinite mode', function () {
    $table             = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->loadedRows = 200;

    $table->updatedSearch();

    expect($table->loadedRows)->toBe(50);
});

it('resets loadedRows on applyActiveFilters when in infinite mode', function () {
    $table             = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->loadedRows = 150;

    $table->applyActiveFilters();

    expect($table->loadedRows)->toBe(50);
});

it('resets loadedRows on clearFilters when in infinite mode', function () {
    $table             = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->loadedRows = 150;

    $table->clearFilters();

    expect($table->loadedRows)->toBe(50);
});

it('resets loadedRows on removeFilter when in infinite mode', function () {
    $table                = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->activeFilters = ['status' => 'active'];
    $table->loadedRows    = 150;

    $table->removeFilter('status');

    expect($table->loadedRows)->toBe(50);
});

// ---------------------------------------------------------------------------
// updatedPerPage()
// ---------------------------------------------------------------------------

it('sets loadedRows when switching to infinite mode via updatedPerPage', function () {
    $table          = makeInfiniteTable(perPage: 0, chunkSize: 50);
    $table->perPage = 0;

    $table->updatedPerPage();

    expect($table->loadedRows)->toBe(50);
});

it('clears loadedRows when switching away from infinite mode via updatedPerPage', function () {
    $table             = makeInfiniteTable(perPage: 25);
    $table->loadedRows = 150;

    $table->updatedPerPage();

    expect($table->loadedRows)->toBe(0);
});
