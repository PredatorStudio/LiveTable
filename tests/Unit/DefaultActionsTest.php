<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use PredatorStudio\LiveTable\RowAction;

uses(\Orchestra\Testbench\TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Stub model (reused from DefaultCreatingTest – guarded against redefinition)
// ---------------------------------------------------------------------------

if (! class_exists('FakeActionsModel')) {
    class FakeActionsModel extends \Illuminate\Database\Eloquent\Model
    {
        protected $table    = 'fake_actions_models';
        protected $fillable = ['name', 'email'];

        public static function create(array $attributes = []): static
        {
            return new static($attributes);
        }
    }
}

// ---------------------------------------------------------------------------
// Helper – BaseTable stub with faked query returning given rows
// ---------------------------------------------------------------------------

function makeActionsTable(
    array $rows = [],
    string $model = '',
    bool $defaultActions = true,
    bool $defaultActionEdit = true,
    bool $defaultActionDelete = true,
    ?Builder $queryOverride = null,
): BaseTable {
    return new class (
        $rows,
        $model,
        $defaultActions,
        $defaultActionEdit,
        $defaultActionDelete,
        $queryOverride,
    ) extends BaseTable {
        public bool  $beforeDeleteCalled = false;
        public bool  $afterDeleteCalled  = false;
        public bool  $beforeUpdateCalled = false;
        public bool  $afterUpdateCalled  = false;
        public mixed $deletedRecord      = null;
        public string $deletedId         = '';
        public mixed $updatedRecord      = null;
        public array $receivedUpdateData = [];

        public function __construct(
            private array    $rows,
            string           $modelClass,
            bool             $da,
            bool             $daEdit,
            bool             $daDelete,
            private ?Builder $queryOverride,
        ) {
            $this->model               = $modelClass;
            $this->defaultActions      = $da;
            $this->defaultActionEdit   = $daEdit;
            $this->defaultActionDelete = $daDelete;
        }

        protected function baseQuery(): Builder
        {
            if ($this->queryOverride) {
                return $this->queryOverride;
            }

            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return $this->editingData;
        }

        protected function beforeDelete(mixed $record): void
        {
            $this->beforeDeleteCalled = true;
            $this->deletedRecord      = $record;
        }

        protected function afterDelete(string $id): void
        {
            $this->afterDeleteCalled = true;
            $this->deletedId         = $id;
        }

        protected function beforeUpdate(mixed $record, array &$data): void
        {
            $this->beforeUpdateCalled  = true;
            $this->updatedRecord       = $record;
            $this->receivedUpdateData  = $data;
        }

        protected function afterUpdate(mixed $record): void
        {
            $this->afterUpdateCalled = true;
            $this->updatedRecord     = $record;
        }
    };
}

// ---------------------------------------------------------------------------
// Property defaults
// ---------------------------------------------------------------------------

it('defaultActions defaults to false', function () {
    $table = new class extends BaseTable {
        public function __construct() {}
        protected function baseQuery(): Builder { return Mockery::mock(Builder::class); }
        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $prop = new ReflectionProperty($table, 'defaultActions');
    expect($prop->getValue($table))->toBeFalse();
});

it('defaultActionEdit defaults to true', function () {
    $table = new class extends BaseTable {
        public function __construct() {}
        protected function baseQuery(): Builder { return Mockery::mock(Builder::class); }
        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $prop = new ReflectionProperty($table, 'defaultActionEdit');
    expect($prop->getValue($table))->toBeTrue();
});

it('defaultActionDelete defaults to true', function () {
    $table = new class extends BaseTable {
        public function __construct() {}
        protected function baseQuery(): Builder { return Mockery::mock(Builder::class); }
        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $prop = new ReflectionProperty($table, 'defaultActionDelete');
    expect($prop->getValue($table))->toBeTrue();
});

it('showEditingModal defaults to false', function () {
    $table = makeActionsTable();
    expect($table->showEditingModal)->toBeFalse();
});

it('editingId defaults to empty string', function () {
    $table = makeActionsTable();
    expect($table->editingId)->toBe('');
});

it('editingData defaults to empty array', function () {
    $table = makeActionsTable();
    expect($table->editingData)->toBe([]);
});

// ---------------------------------------------------------------------------
// deleteRow()
// ---------------------------------------------------------------------------

it('deleteRow does nothing when defaultActions is false', function () {
    $record  = new \stdClass;
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->never();

    $table = makeActionsTable(defaultActions: false, queryOverride: $builder);
    $table->deleteRow('1');
});

it('deleteRow does nothing when defaultActionDelete is false', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->never();

    $table = makeActionsTable(defaultActionDelete: false, queryOverride: $builder);
    $table->deleteRow('1');
});

it('deleteRow calls beforeDelete with record', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->once()->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->with('id', '5')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeActionsTable(queryOverride: $builder);
    $table->deleteRow('5');

    expect($table->beforeDeleteCalled)->toBeTrue()
        ->and($table->deletedRecord)->toBe($record);
});

it('deleteRow calls afterDelete with id string', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->once()->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeActionsTable(queryOverride: $builder);
    $table->deleteRow('7');

    expect($table->afterDeleteCalled)->toBeTrue()
        ->and($table->deletedId)->toBe('7');
});

it('deleteRow removes id from selected', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->once()->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table           = makeActionsTable(queryOverride: $builder);
    $table->selected = ['3', '5', '7'];
    $table->deleteRow('3');

    expect($table->selected)->not->toContain('3')
        ->and($table->selected)->toContain('5')
        ->and($table->selected)->toContain('7');
});

it('deleteRow aborts when beforeDelete throws', function () {
    $record  = (object) ['id' => 1];
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);
    $builder->shouldReceive('delete')->never();

    $table = new class ($builder) extends BaseTable {
        public function __construct(private Builder $mock)
        {
            $this->defaultActions      = true;
            $this->defaultActionDelete = true;
        }

        protected function baseQuery(): Builder { return $this->mock; }
        public function columns(): array { return [Column::make('id', 'ID')]; }

        protected function beforeDelete(mixed $record): void
        {
            throw new \RuntimeException('Not allowed');
        }
    };

    expect(fn () => $table->deleteRow('1'))->toThrow(\RuntimeException::class, 'Not allowed');
});

// ---------------------------------------------------------------------------
// openEditingModal()
// ---------------------------------------------------------------------------

it('openEditingModal does nothing when defaultActions is false', function () {
    $table = makeActionsTable(defaultActions: false, model: FakeActionsModel::class);
    $table->openEditingModal('1');

    expect($table->showEditingModal)->toBeFalse();
});

it('openEditingModal does nothing when defaultActionEdit is false', function () {
    $table = makeActionsTable(defaultActionEdit: false, model: FakeActionsModel::class);
    $table->openEditingModal('1');

    expect($table->showEditingModal)->toBeFalse();
});

it('openEditingModal does nothing when model is not set', function () {
    $table = makeActionsTable(model: '');
    $table->openEditingModal('1');

    expect($table->showEditingModal)->toBeFalse();
});

it('openEditingModal sets showEditingModal to true', function () {
    $record  = (object) ['id' => 1, 'name' => 'Jan', 'email' => 'j@example.com'];
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->openEditingModal('1');

    expect($table->showEditingModal)->toBeTrue();
});

it('openEditingModal sets editingId', function () {
    $record  = (object) ['id' => 42, 'name' => 'Test', 'email' => 't@example.com'];
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->openEditingModal('42');

    expect($table->editingId)->toBe('42');
});

it('openEditingModal populates editingData from record', function () {
    $record  = (object) ['id' => 1, 'name' => 'Anna', 'email' => 'a@example.com'];
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->openEditingModal('1');

    expect($table->editingData['name'])->toBe('Anna')
        ->and($table->editingData['email'])->toBe('a@example.com');
});

it('openEditingModal only loads fillable keys', function () {
    $record = (object) ['id' => 1, 'name' => 'Anna', 'email' => 'a@example.com', 'secret' => 'hidden'];
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->openEditingModal('1');

    expect($table->editingData)->not->toHaveKey('secret')
        ->and($table->editingData)->not->toHaveKey('id');
});

// ---------------------------------------------------------------------------
// updateRecord()
// ---------------------------------------------------------------------------

it('updateRecord does nothing when editingId is empty', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->never();

    $table = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->editingId = '';
    $table->updateRecord();
});

it('updateRecord does nothing when defaultActions is false', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->never();

    $table           = makeActionsTable(defaultActions: false, model: FakeActionsModel::class, queryOverride: $builder);
    $table->editingId   = '5';
    $table->updateRecord();
});

it('updateRecord calls beforeUpdate hook', function () {
    $record = Mockery::mock();
    $record->shouldReceive('update')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table               = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->editingId    = '1';
    $table->editingData  = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->updateRecord();

    expect($table->beforeUpdateCalled)->toBeTrue();
});

it('updateRecord calls afterUpdate hook', function () {
    $record = Mockery::mock();
    $record->shouldReceive('update')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table               = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->editingId    = '1';
    $table->editingData  = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->updateRecord();

    expect($table->afterUpdateCalled)->toBeTrue();
});

it('updateRecord closes modal and resets state', function () {
    $record = Mockery::mock();
    $record->shouldReceive('update')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table                   = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->editingId        = '1';
    $table->editingData      = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->showEditingModal = true;
    $table->updateRecord();

    expect($table->showEditingModal)->toBeFalse()
        ->and($table->editingData)->toBe([])
        ->and($table->editingId)->toBe('');
});

it('beforeUpdate can modify data by reference', function () {
    $captured = [];
    $record   = Mockery::mock();
    $record->shouldReceive('update')->with(Mockery::on(function ($data) use (&$captured) {
        $captured = $data;
        return true;
    }))->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = new class ($builder) extends BaseTable {
        public function __construct(private Builder $mock)
        {
            $this->model               = FakeActionsModel::class;
            $this->defaultActions      = true;
            $this->defaultActionEdit   = true;
            $this->editingId           = '1';
            $this->editingData         = ['name' => 'Original', 'email' => 'o@b.com'];
        }

        protected function baseQuery(): Builder { return $this->mock; }
        public function columns(): array { return [Column::make('id', 'ID')]; }
        public function validate($rules = null, $messages = [], $attributes = []): array { return []; }

        protected function beforeUpdate(mixed $record, array &$data): void
        {
            $data['name'] = 'Modified';
        }
    };

    $table->updateRecord();

    expect($captured['name'])->toBe('Modified');
});

it('updateRecord only passes fillable keys to update()', function () {
    $captured = [];
    $record   = Mockery::mock();
    $record->shouldReceive('update')->with(Mockery::on(function ($data) use (&$captured) {
        $captured = $data;
        return true;
    }))->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table               = makeActionsTable(model: FakeActionsModel::class, queryOverride: $builder);
    $table->editingId    = '1';
    // Inject extra key not in fillable
    $table->editingData  = ['name' => 'Jan', 'email' => 'j@b.com', '__injected' => 'evil'];
    $table->updateRecord();

    expect($captured)->not->toHaveKey('__injected');
});

// ---------------------------------------------------------------------------
// editingRules()
// ---------------------------------------------------------------------------

it('editingRules returns required rule for each fillable field', function () {
    $table = makeActionsTable(model: FakeActionsModel::class);
    $rules = $table->editingRules();

    expect($rules)->toHaveKey('editingData.name')
        ->and($rules['editingData.name'])->toContain('required');
});

it('editingRules returns empty array when no model set', function () {
    $table = makeActionsTable(model: '');
    expect($table->editingRules())->toBe([]);
});

// ---------------------------------------------------------------------------
// Default actions appended in render()
// ---------------------------------------------------------------------------

it('default edit and delete actions appear in rowActionsMap when defaultActions is true', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class ($rows) extends BaseTable {
        public function __construct(private array $rows)
        {
            $this->model               = FakeActionsModel::class;
            $this->defaultActions      = true;
            $this->defaultActionEdit   = true;
            $this->defaultActionDelete = true;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));
            return $builder;
        }

        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $actions  = $viewData['rowActionsMap']['1'];
    $methods  = array_column($actions, 'method');

    expect($methods)->toContain('openEditingModal')
        ->and($methods)->toContain('deleteRow');
});

it('only delete action appears when defaultActionEdit is false', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class ($rows) extends BaseTable {
        public function __construct(private array $rows)
        {
            $this->model               = FakeActionsModel::class;
            $this->defaultActions      = true;
            $this->defaultActionEdit   = false;
            $this->defaultActionDelete = true;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));
            return $builder;
        }

        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $methods  = array_column($viewData['rowActionsMap']['1'], 'method');

    expect($methods)->not->toContain('openEditingModal')
        ->and($methods)->toContain('deleteRow');
});

it('only edit action appears when defaultActionDelete is false', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class ($rows) extends BaseTable {
        public function __construct(private array $rows)
        {
            $this->model               = FakeActionsModel::class;
            $this->defaultActions      = true;
            $this->defaultActionEdit   = true;
            $this->defaultActionDelete = false;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));
            return $builder;
        }

        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $methods  = array_column($viewData['rowActionsMap']['1'], 'method');

    expect($methods)->toContain('openEditingModal')
        ->and($methods)->not->toContain('deleteRow');
});

it('no default actions appear when defaultActions is false', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class ($rows) extends BaseTable {
        public function __construct(private array $rows)
        {
            $this->model          = FakeActionsModel::class;
            $this->defaultActions = false;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));
            return $builder;
        }

        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $methods  = array_column($viewData['rowActionsMap']['1'] ?? [], 'method');

    expect($methods)->not->toContain('openEditingModal')
        ->and($methods)->not->toContain('deleteRow');
});

it('default actions are appended after custom rowActions', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class ($rows) extends BaseTable {
        public function __construct(private array $rows)
        {
            $this->model               = FakeActionsModel::class;
            $this->defaultActions      = true;
            $this->defaultActionEdit   = true;
            $this->defaultActionDelete = true;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));
            return $builder;
        }

        public function columns(): array { return [Column::make('id', 'ID')]; }

        public function rowActions(mixed $row): array
        {
            return [RowAction::make('Custom')->method('customMethod')];
        }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $methods  = array_column($viewData['rowActionsMap']['1'], 'method');

    // Custom first, then defaults
    expect($methods[0])->toBe('customMethod')
        ->and($methods)->toContain('openEditingModal')
        ->and($methods)->toContain('deleteRow');
});

it('delete action has wire:confirm text', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class ($rows) extends BaseTable {
        public function __construct(private array $rows)
        {
            $this->model               = FakeActionsModel::class;
            $this->defaultActions      = true;
            $this->defaultActionDelete = true;
            $this->defaultActionEdit   = false;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));
            return $builder;
        }

        public function columns(): array { return [Column::make('id', 'ID')]; }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $deleteAction = collect($viewData['rowActionsMap']['1'])->firstWhere('method', 'deleteRow');

    expect($deleteAction->confirm)->not->toBe('');
});
