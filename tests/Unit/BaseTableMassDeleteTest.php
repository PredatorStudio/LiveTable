<?php

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeMassDeleteTable(
    array $selected = [],
    bool $selectAllQuery = false,
    bool $selectable = true,
    bool $massDelete = true,
    ?Builder $builder = null,
): BaseTable {
    return new class ($selected, $selectAllQuery, $selectable, $massDelete, $builder) extends BaseTable {
        public function __construct(
            array $sel,
            bool $allQuery,
            bool $selectable,
            bool $md,
            private ?Builder $mock,
        ) {
            $this->selected       = $sel;
            $this->selectAllQuery = $allQuery;
            $this->selectable     = $selectable;
            $this->massDelete     = $md;
        }

        protected function baseQuery(): Builder
        {
            return $this->mock ?? Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };
}

function makeBuilder(
    array $selected = [1, 2],
    bool $expectWhereIn = true,
    bool $expectDelete = true,
    bool $expectPluck = false,
): Builder {
    $builder = Mockery::mock(Builder::class);

    if ($expectWhereIn) {
        $builder->shouldReceive('whereIn')->once()->andReturnSelf();
    }

    if ($expectPluck) {
        $builder->shouldReceive('pluck')->with('id')->once()->andReturn(collect($selected));
    }

    if ($expectDelete) {
        $builder->shouldReceive('delete')->once();
    }

    return $builder;
}

// ---------------------------------------------------------------------------
// Guard – early return
// ---------------------------------------------------------------------------

it('does nothing when selectable is false', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('delete')->never();

    $table = makeMassDeleteTable(selected: [1, 2], selectable: false, builder: $builder);
    $table->massDelete();
});

it('does nothing when no rows selected and selectAllQuery is false', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('delete')->never();

    $table = makeMassDeleteTable(selected: [], selectAllQuery: false, builder: $builder);
    $table->massDelete();
});

// ---------------------------------------------------------------------------
// Deletion – selected IDs mode
// ---------------------------------------------------------------------------

it('calls whereIn and delete for selected ids', function () {
    $builder = makeBuilder(selected: [1, 2], expectWhereIn: true, expectDelete: true);

    $table = makeMassDeleteTable(selected: [1, 2], builder: $builder);
    $table->massDelete();
});

it('does not call pluck when using selected ids mode', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->once()->andReturnSelf();
    $builder->shouldReceive('delete')->once();
    $builder->shouldReceive('pluck')->never();

    $table = makeMassDeleteTable(selected: [1, 2], builder: $builder);
    $table->massDelete();
});

// ---------------------------------------------------------------------------
// Deletion – selectAllQuery mode
// ---------------------------------------------------------------------------

it('calls pluck and delete without whereIn for selectAllQuery mode', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->with('id')->once()->andReturn(collect([1, 2, 3]));
    $builder->shouldReceive('delete')->once();
    $builder->shouldReceive('whereIn')->never();

    $table = makeMassDeleteTable(selected: [], selectAllQuery: true, builder: $builder);
    $table->massDelete();
});

it('uses pluck not get for selectAllQuery mode', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->with('id')->once()->andReturn(collect([5]));
    $builder->shouldReceive('delete')->once();
    $builder->shouldReceive('get')->never();

    $table = makeMassDeleteTable(selected: [], selectAllQuery: true, builder: $builder);
    $table->massDelete();
});

// ---------------------------------------------------------------------------
// State reset after deletion
// ---------------------------------------------------------------------------

it('clears selected after mass delete', function () {
    $builder = makeBuilder(selected: [1, 2]);

    $table = makeMassDeleteTable(selected: [1, 2], builder: $builder);
    $table->massDelete();

    expect($table->selected)->toBe([]);
});

it('resets page to 1 after mass delete', function () {
    $builder = makeBuilder(selected: [3]);

    $table        = makeMassDeleteTable(selected: [3], builder: $builder);
    $table->page  = 5;
    $table->massDelete();

    expect($table->page)->toBe(1);
});

it('resets selectAllQuery to false after mass delete', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->with('id')->andReturn(collect([1]));
    $builder->shouldReceive('delete')->once();

    $table = makeMassDeleteTable(selected: [], selectAllQuery: true, builder: $builder);
    $table->massDelete();

    expect($table->selectAllQuery)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Hooks
// ---------------------------------------------------------------------------

it('calls beforeMassDelete hook with ids before deletion', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('delete');

    $table = new class ($builder) extends BaseTable {
        public bool $beforeCalled = false;
        public array $receivedIds = [];

        public function __construct(private Builder $mock)
        {
            $this->selectable = true;
            $this->massDelete = true;
            $this->selected   = [10, 20];
        }

        protected function baseQuery(): Builder { return $this->mock; }

        public function columns(): array { return [Column::make('id', 'ID')]; }

        protected function beforeMassDelete(array $ids): void
        {
            $this->beforeCalled = true;
            $this->receivedIds  = $ids;
        }
    };

    $table->massDelete();

    expect($table->beforeCalled)->toBeTrue()
        ->and($table->receivedIds)->toBe([10, 20]);
});

it('calls afterMassDelete hook with ids after successful deletion', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('delete');

    $table = new class ($builder) extends BaseTable {
        public bool $afterCalled = false;
        public array $receivedIds = [];

        public function __construct(private Builder $mock)
        {
            $this->selectable = true;
            $this->massDelete = true;
            $this->selected   = [7, 8];
        }

        protected function baseQuery(): Builder { return $this->mock; }

        public function columns(): array { return [Column::make('id', 'ID')]; }

        protected function afterMassDelete(array $ids): void
        {
            $this->afterCalled = true;
            $this->receivedIds = $ids;
        }
    };

    $table->massDelete();

    expect($table->afterCalled)->toBeTrue()
        ->and($table->receivedIds)->toBe([7, 8]);
});

it('aborts deletion when beforeMassDelete throws', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('delete')->never();

    $table = new class ($builder) extends BaseTable {
        public function __construct(private Builder $mock)
        {
            $this->selectable = true;
            $this->massDelete = true;
            $this->selected   = [1];
        }

        protected function baseQuery(): Builder { return $this->mock; }

        public function columns(): array { return [Column::make('id', 'ID')]; }

        protected function beforeMassDelete(array $ids): void
        {
            throw new \RuntimeException('Not authorized');
        }
    };

    expect(fn () => $table->massDelete())->toThrow(\RuntimeException::class, 'Not authorized');
});

it('does not call afterMassDelete when deletion throws', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('delete')->once()->andThrow(new \RuntimeException('DB error'));

    $table = new class ($builder) extends BaseTable {
        public bool $afterCalled = false;

        public function __construct(private Builder $mock)
        {
            $this->selectable = true;
            $this->massDelete = true;
            $this->selected   = [1];
        }

        protected function baseQuery(): Builder { return $this->mock; }

        public function columns(): array { return [Column::make('id', 'ID')]; }

        protected function afterMassDelete(array $ids): void
        {
            $this->afterCalled = true;
        }
    };

    expect(fn () => $table->massDelete())->toThrow(\RuntimeException::class);
    expect($table->afterCalled)->toBeFalse();
});

it('passes plucked ids to hooks in selectAllQuery mode', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->with('id')->andReturn(collect([100, 200, 300]));
    $builder->shouldReceive('delete');

    $table = new class ($builder) extends BaseTable {
        public array $beforeIds = [];
        public array $afterIds  = [];

        public function __construct(private Builder $mock)
        {
            $this->selectable     = true;
            $this->massDelete     = true;
            $this->selectAllQuery = true;
            $this->selected       = [];
        }

        protected function baseQuery(): Builder { return $this->mock; }

        public function columns(): array { return [Column::make('id', 'ID')]; }

        protected function beforeMassDelete(array $ids): void { $this->beforeIds = $ids; }

        protected function afterMassDelete(array $ids): void { $this->afterIds = $ids; }
    };

    $table->massDelete();

    expect($table->beforeIds)->toBe([100, 200, 300])
        ->and($table->afterIds)->toBe([100, 200, 300]);
});

// ---------------------------------------------------------------------------
// Property
// ---------------------------------------------------------------------------

it('massDelete property defaults to false', function () {
    $table = new class extends BaseTable {
        public function __construct() {}

        protected function baseQuery(): Builder { return Mockery::mock(Builder::class); }

        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $prop = new \ReflectionProperty($table, 'massDelete');
    $prop->setAccessible(true);

    expect($prop->getValue($table))->toBeFalse();
});