<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
// Stub model
// ---------------------------------------------------------------------------

if (! class_exists('FakeMassEditModel')) {
    class FakeMassEditModel extends Model
    {
        protected $table = 'fake_mass_edit_models';

        protected $fillable = ['name', 'status', 'bio'];
    }
}

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeMassEditTable(
    array $selected = [],
    bool $selectAllQuery = false,
    bool $massEdit = true,
    bool $selectable = true,
    string $model = FakeMassEditModel::class,
    ?Builder $builder = null,
): BaseTable {
    return new class($selected, $selectAllQuery, $massEdit, $selectable, $model, $builder) extends BaseTable
    {
        public bool $beforeCalled = false;

        public bool $afterCalled = false;

        public array $receivedIds = [];

        public array $receivedData = [];

        public function __construct(
            array $sel,
            bool $allQuery,
            bool $me,
            bool $selectable,
            string $modelClass,
            private ?Builder $mock,
        ) {
            $this->selected = $sel;
            $this->selectAllQuery = $allQuery;
            $this->massEdit = $me;
            $this->selectable = $selectable;
            $this->model = $modelClass;
        }

        protected function baseQuery(): Builder
        {
            return $this->mock ?? Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return $this->massEditData;
        }

        protected function beforeMassEdit(array $ids, array &$data): void
        {
            $this->beforeCalled = true;
            $this->receivedIds = $ids;
            $this->receivedData = $data;
        }

        protected function afterMassEdit(array $ids): void
        {
            $this->afterCalled = true;
            $this->receivedIds = $ids;
        }
    };
}

// ---------------------------------------------------------------------------
// Property defaults
// ---------------------------------------------------------------------------

it('massEdit defaults to false', function () {
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

    $prop = new ReflectionProperty($table, 'massEdit');
    expect($prop->getValue($table))->toBeFalse();
});

it('showMassEditModal defaults to false', function () {
    $table = makeMassEditTable();
    expect($table->showMassEditModal)->toBeFalse();
});

it('massEditData defaults to empty array', function () {
    $table = makeMassEditTable();
    expect($table->massEditData)->toBe([]);
});

// ---------------------------------------------------------------------------
// openMassEditModal() – guards
// ---------------------------------------------------------------------------

it('openMassEditModal does nothing when massEdit is false', function () {
    $table = makeMassEditTable(selected: ['1'], massEdit: false);
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeFalse();
});

it('openMassEditModal does nothing when selectable is false', function () {
    $table = makeMassEditTable(selected: ['1'], selectable: false);
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeFalse();
});

it('openMassEditModal does nothing when nothing selected and selectAllQuery false', function () {
    $table = makeMassEditTable(selected: [], selectAllQuery: false);
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeFalse();
});

it('openMassEditModal does nothing when model is not set', function () {
    $table = makeMassEditTable(selected: ['1'], model: '');
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeFalse();
});

it('openMassEditModal does nothing when model class does not exist', function () {
    $table = makeMassEditTable(selected: ['1'], model: 'NonExistent\\Model');
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeFalse();
});

// ---------------------------------------------------------------------------
// openMassEditModal() – success path
// ---------------------------------------------------------------------------

it('openMassEditModal sets showMassEditModal to true', function () {
    $table = makeMassEditTable(selected: ['1', '2']);
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeTrue();
});

it('openMassEditModal initializes massEditData with empty strings', function () {
    $table = makeMassEditTable(selected: ['1']);
    $table->openMassEditModal();

    expect($table->massEditData)->toHaveKeys(['name', 'status', 'bio']);
    foreach ($table->massEditData as $v) {
        expect($v)->toBe('');
    }
});

it('openMassEditModal works when selectAllQuery is true even with empty selected', function () {
    $table = makeMassEditTable(selected: [], selectAllQuery: true);
    $table->openMassEditModal();

    expect($table->showMassEditModal)->toBeTrue();
});

// ---------------------------------------------------------------------------
// massEditUpdate() – guards
// ---------------------------------------------------------------------------

it('massEditUpdate does nothing when massEdit is false', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->never();
    $builder->shouldReceive('update')->never();

    $table = makeMassEditTable(selected: ['1'], massEdit: false, builder: $builder);
    $table->massEditData = ['name' => 'Jan'];
    $table->massEditUpdate();
});

it('massEditUpdate closes modal and does nothing when all fields empty', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('update')->never();

    $table = makeMassEditTable(selected: ['1'], builder: $builder);
    $table->massEditData = ['name' => '', 'status' => '', 'bio' => ''];
    $table->showMassEditModal = true;
    $table->massEditUpdate();

    expect($table->showMassEditModal)->toBeFalse();
});

// ---------------------------------------------------------------------------
// massEditUpdate() – selected IDs mode
// ---------------------------------------------------------------------------

it('massEditUpdate calls whereIn with selected ids on baseQuery', function () {
    $builder = Mockery::mock(Builder::class);
    // baseQuery() called twice: once for buildQuery (unused in selected mode), once for update
    $builder->shouldReceive('whereIn')->with('id', ['1', '2'])->once()->andReturnSelf();
    $builder->shouldReceive('update')->once()->andReturn(2);

    $table = makeMassEditTable(selected: ['1', '2'], builder: $builder);
    $table->massEditData = ['name' => 'Jan', 'status' => '', 'bio' => ''];
    $table->massEditUpdate();
});

it('massEditUpdate only sends non-empty fields to update()', function () {
    $captured = null;
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->once()
        ->with(Mockery::on(function ($data) use (&$captured) {
            $captured = $data;

            return true;
        }))->andReturn(1);

    $table = makeMassEditTable(selected: ['1'], builder: $builder);
    $table->massEditData = ['name' => 'Anna', 'status' => '', 'bio' => ''];
    $table->massEditUpdate();

    expect($captured)->toHaveKey('name')
        ->and($captured)->not->toHaveKey('status')
        ->and($captured)->not->toHaveKey('bio');
});

it('massEditUpdate only sends fillable keys', function () {
    $captured = null;
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->once()
        ->with(Mockery::on(function ($data) use (&$captured) {
            $captured = $data;

            return true;
        }))->andReturn(1);

    $table = makeMassEditTable(selected: ['1'], builder: $builder);
    $table->massEditData = ['name' => 'Anna', '__injected' => 'evil'];
    $table->massEditUpdate();

    expect($captured)->not->toHaveKey('__injected');
});

// ---------------------------------------------------------------------------
// massEditUpdate() – selectAllQuery mode
// ---------------------------------------------------------------------------

it('massEditUpdate uses pluck in selectAllQuery mode', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->with('id')->once()->andReturn(collect(['1', '2', '3']));
    $builder->shouldReceive('whereIn')->with('id', ['1', '2', '3'])->once()->andReturnSelf();
    $builder->shouldReceive('update')->once()->andReturn(3);

    $table = makeMassEditTable(selected: [], selectAllQuery: true, builder: $builder);
    $table->massEditData = ['name' => 'Test'];
    $table->massEditUpdate();
});

it('massEditUpdate does not call whereIn twice in selectAllQuery mode', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->with('id')->once()->andReturn(collect([10, 20]));
    $builder->shouldReceive('whereIn')->once()->andReturnSelf();
    $builder->shouldReceive('update')->once()->andReturn(2);

    $table = makeMassEditTable(selected: [], selectAllQuery: true, builder: $builder);
    $table->massEditData = ['status' => 'active'];
    $table->massEditUpdate();
});

// ---------------------------------------------------------------------------
// Hooks
// ---------------------------------------------------------------------------

it('massEditUpdate calls beforeMassEdit with ids and data', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: ['5', '6'], builder: $builder);
    $table->massEditData = ['name' => 'X'];
    $table->massEditUpdate();

    expect($table->beforeCalled)->toBeTrue()
        ->and($table->receivedIds)->toBe(['5', '6'])
        ->and($table->receivedData)->toHaveKey('name');
});

it('massEditUpdate calls afterMassEdit with ids after update', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: ['3'], builder: $builder);
    $table->massEditData = ['status' => 'done'];
    $table->massEditUpdate();

    expect($table->afterCalled)->toBeTrue()
        ->and($table->receivedIds)->toBe(['3']);
});

it('beforeMassEdit can modify data by reference', function () {
    $captured = null;
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')
        ->with(Mockery::on(function ($data) use (&$captured) {
            $captured = $data;

            return true;
        }))->andReturn(1);

    $table = new class($builder) extends BaseTable
    {
        public function __construct(private Builder $mock)
        {
            $this->massEdit = true;
            $this->selectable = true;
            $this->model = FakeMassEditModel::class;
            $this->selected = ['1'];
            $this->massEditData = ['name' => 'Original'];
        }

        protected function baseQuery(): Builder
        {
            return $this->mock;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($r = null, $m = [], $a = []): array
        {
            return [];
        }

        protected function beforeMassEdit(array $ids, array &$data): void
        {
            $data['name'] = 'Modified';
        }
    };

    $table->massEditUpdate();

    expect($captured['name'])->toBe('Modified');
});

it('beforeMassEdit throw aborts update', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->never();

    $table = new class($builder) extends BaseTable
    {
        public function __construct(private Builder $mock)
        {
            $this->massEdit = true;
            $this->selectable = true;
            $this->model = FakeMassEditModel::class;
            $this->selected = ['1'];
            $this->massEditData = ['name' => 'X'];
        }

        protected function baseQuery(): Builder
        {
            return $this->mock;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($r = null, $m = [], $a = []): array
        {
            return [];
        }

        protected function beforeMassEdit(array $ids, array &$data): void
        {
            throw new RuntimeException('Aborted');
        }
    };

    expect(fn () => $table->massEditUpdate())->toThrow(RuntimeException::class, 'Aborted');
});

// ---------------------------------------------------------------------------
// State reset after update
// ---------------------------------------------------------------------------

it('massEditUpdate closes modal after update', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: ['1'], builder: $builder);
    $table->massEditData = ['name' => 'Test'];
    $table->showMassEditModal = true;
    $table->massEditUpdate();

    expect($table->showMassEditModal)->toBeFalse();
});

it('massEditUpdate resets massEditData after update', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: ['1'], builder: $builder);
    $table->massEditData = ['name' => 'Test'];
    $table->massEditUpdate();

    expect($table->massEditData)->toBe([]);
});

it('massEditUpdate clears selected after update', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: ['1', '2'], builder: $builder);
    $table->massEditData = ['name' => 'Test'];
    $table->massEditUpdate();

    expect($table->selected)->toBe([]);
});

it('massEditUpdate resets selectAllQuery after update', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('pluck')->andReturn(collect(['1']));
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: [], selectAllQuery: true, builder: $builder);
    $table->massEditData = ['name' => 'Test'];
    $table->massEditUpdate();

    expect($table->selectAllQuery)->toBeFalse();
});

it('massEditUpdate resets page to 1', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeMassEditTable(selected: ['1'], builder: $builder);
    $table->massEditData = ['name' => 'Test'];
    $table->page = 5;
    $table->massEditUpdate();

    expect($table->page)->toBe(1);
});

// ---------------------------------------------------------------------------
// massEditRules()
// ---------------------------------------------------------------------------

it('massEditRules returns empty array by default (no required)', function () {
    $table = makeMassEditTable();
    expect($table->massEditRules())->toBe([]);
});
