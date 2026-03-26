<?php

use Illuminate\Database\Eloquent\Builder;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helper – calls private buildPageLinks() via reflection
// ---------------------------------------------------------------------------

function callBuildPageLinks(BaseTable $table, int $lastPage): array
{
    $method = new ReflectionMethod(BaseTable::class, 'buildPageLinks');
    $method->setAccessible(true);

    return $method->invoke($table, $lastPage);
}

function makePageLinksTable(int $currentPage = 1): BaseTable
{
    $table = new class extends BaseTable
    {
        public function __construct() {}

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };

    $table->page = $currentPage;

    return $table;
}

// ---------------------------------------------------------------------------
// Edge cases
// ---------------------------------------------------------------------------

it('returns empty array when lastPage is 0', function () {
    $table = makePageLinksTable();
    expect(callBuildPageLinks($table, 0))->toBe([]);
});

it('returns empty array when lastPage is 1', function () {
    $table = makePageLinksTable();
    expect(callBuildPageLinks($table, 1))->toBe([]);
});

// ---------------------------------------------------------------------------
// Small range – no ellipsis needed
// ---------------------------------------------------------------------------

it('returns all pages for small range (5 pages)', function () {
    $table = makePageLinksTable(currentPage: 3);
    $links = callBuildPageLinks($table, 5);

    expect($links)->toBe([1, 2, 3, 4, 5]);
});

// ---------------------------------------------------------------------------
// First and last pages always present
// ---------------------------------------------------------------------------

it('always includes page 1', function () {
    $table = makePageLinksTable(currentPage: 10);
    $links = callBuildPageLinks($table, 20);

    expect($links[0])->toBe(1);
});

it('always includes last page', function () {
    $table = makePageLinksTable(currentPage: 1);
    $links = callBuildPageLinks($table, 20);

    expect(last($links))->toBe(20);
});

// ---------------------------------------------------------------------------
// Ellipsis insertion
// ---------------------------------------------------------------------------

it('inserts ellipsis when there is a gap between neighbors and last page', function () {
    $table = makePageLinksTable(currentPage: 1);
    // page 1, neighbors: 1,2,3 → lastPage=20 → gap between 3 and 20
    $links = callBuildPageLinks($table, 20);

    expect($links)->toContain('...');
});

it('inserts ellipsis when there is a gap between first page and current neighbors', function () {
    $table = makePageLinksTable(currentPage: 15);
    // current=15, neighbors: 13..17 → page 1 is far away
    $links = callBuildPageLinks($table, 20);

    expect($links)->toContain('...');
});

it('can have two ellipsis segments when current is in middle of large range', function () {
    $table = makePageLinksTable(currentPage: 10);
    $links = callBuildPageLinks($table, 20);

    $ellipsisCount = count(array_filter($links, fn ($v) => $v === '...'));
    expect($ellipsisCount)->toBe(2);
});

// ---------------------------------------------------------------------------
// Neighbors: current ±2
// ---------------------------------------------------------------------------

it('includes current page ±2 neighbors in links', function () {
    $table = makePageLinksTable(currentPage: 10);
    $links = callBuildPageLinks($table, 20);

    expect($links)->toContain(8)
        ->and($links)->toContain(9)
        ->and($links)->toContain(10)
        ->and($links)->toContain(11)
        ->and($links)->toContain(12);
});

