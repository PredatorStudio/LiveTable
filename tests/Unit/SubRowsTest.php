<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;
use PredatorStudio\LiveTable\SubRows;

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// fromArray()
// ---------------------------------------------------------------------------

it('creates SubRows from array', function () {
    $items = [['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta']];

    $subRows = SubRows::fromArray($items);

    expect($subRows->getItems())->toBe($items);
});

it('creates empty SubRows from empty array', function () {
    $subRows = SubRows::fromArray([]);

    expect($subRows->isEmpty())->toBeTrue()
        ->and($subRows->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// fromCollection()
// ---------------------------------------------------------------------------

it('creates SubRows from Eloquent Collection', function () {
    $collection = Collection::make([
        (object) ['id' => 1, 'project' => 'Alpha'],
        (object) ['id' => 2, 'project' => 'Beta'],
    ]);

    $subRows = SubRows::fromCollection($collection);

    expect($subRows->count())->toBe(2)
        ->and($subRows->isEmpty())->toBeFalse();
});

it('creates empty SubRows from empty Collection', function () {
    $subRows = SubRows::fromCollection(Collection::make([]));

    expect($subRows->isEmpty())->toBeTrue();
});

// ---------------------------------------------------------------------------
// fromQuery()
// ---------------------------------------------------------------------------

it('creates SubRows from Eloquent Builder by calling get()', function () {
    $result = Collection::make([
        (object) ['id' => 10, 'value' => 'X'],
    ]);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('get')->once()->andReturn($result);

    $subRows = SubRows::fromQuery($builder);

    expect($subRows->count())->toBe(1);
});

it('fromQuery calls get exactly once', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('get')->once()->andReturn(Collection::make([]));

    SubRows::fromQuery($builder);
});

// ---------------------------------------------------------------------------
// getItems / count / isEmpty
// ---------------------------------------------------------------------------

it('returns correct count', function () {
    $subRows = SubRows::fromArray([['a' => 1], ['a' => 2], ['a' => 3]]);

    expect($subRows->count())->toBe(3);
});

it('is not empty when items exist', function () {
    $subRows = SubRows::fromArray([['x' => 1]]);

    expect($subRows->isEmpty())->toBeFalse();
});

it('getItems returns all items as array', function () {
    $items = [['id' => 1], ['id' => 2]];

    $subRows = SubRows::fromArray($items);

    expect($subRows->getItems())->toHaveCount(2)
        ->and($subRows->getItems()[0])->toBe(['id' => 1]);
});

it('getItems from Collection returns array of objects', function () {
    $obj      = (object) ['id' => 99];
    $subRows  = SubRows::fromCollection(Collection::make([$obj]));

    expect($subRows->getItems()[0])->toBe($obj);
});
