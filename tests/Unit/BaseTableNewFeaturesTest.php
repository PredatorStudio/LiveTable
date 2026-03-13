<?php

/**
 * Tests for todo-3 features:
 *  3.2/3.3 – hasModel() + ?string $model = null
 *  3.5     – $defaultEditIcon / $defaultDeleteIcon
 *  3.7     – preloadSubRows() hook
 *  3.8     – staticRowActions()
 */

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use PredatorStudio\LiveTable\RowAction;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helper – table with faked query
// ---------------------------------------------------------------------------

function makeNewFeaturesTable(array $rows = [], ?callable $customize = null): BaseTable
{
    $table = new class($rows) extends BaseTable
    {
        public bool $preloadCalled = false;

        public ?Collection $preloadedItems = null;

        public int $staticActionsCallCount = 0;

        public function __construct(private array $rows) {}

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

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };

    if ($customize) {
        $customize($table);
    }

    return $table;
}

// ===========================================================================
// 3.2 / 3.3 – hasModel()
// ===========================================================================

it('model property defaults to null', function () {
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

    $prop = new ReflectionProperty($table, 'model');
    expect($prop->getValue($table))->toBeNull();
});

it('hasModel returns false when model is null', function () {
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

    $method = new ReflectionMethod($table, 'hasModel');
    expect($method->invoke($table))->toBeFalse();
});

it('hasModel returns false when model is empty string', function () {
    $table = new class extends BaseTable
    {
        public function __construct()
        {
            $this->model = '';
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

    $method = new ReflectionMethod($table, 'hasModel');
    expect($method->invoke($table))->toBeFalse();
});

it('hasModel returns false when model class does not exist', function () {
    $table = new class extends BaseTable
    {
        public function __construct()
        {
            $this->model = 'NonExistent\\ClassName';
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

    $method = new ReflectionMethod($table, 'hasModel');
    expect($method->invoke($table))->toBeFalse();
});

it('hasModel returns true when model is valid class', function () {
    $table = new class extends BaseTable
    {
        public function __construct()
        {
            $this->model = \stdClass::class;
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

    $method = new ReflectionMethod($table, 'hasModel');
    expect($method->invoke($table))->toBeTrue();
});

// ===========================================================================
// 3.5 – $defaultEditIcon / $defaultDeleteIcon
// ===========================================================================

it('defaultEditIcon property exists and is non-empty string', function () {
    $table = makeNewFeaturesTable();
    $prop = new ReflectionProperty($table, 'defaultEditIcon');
    $icon = $prop->getValue($table);

    expect($icon)->toBeString()->not->toBeEmpty();
});

it('defaultDeleteIcon property exists and is non-empty string', function () {
    $table = makeNewFeaturesTable();
    $prop = new ReflectionProperty($table, 'defaultDeleteIcon');
    $icon = $prop->getValue($table);

    expect($icon)->toBeString()->not->toBeEmpty();
});

it('default edit action in rowActionsMap uses $defaultEditIcon', function () {
    if (! class_exists('DefaultIconModel')) {
        class DefaultIconModel extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'default_icon_models';
            protected $fillable = ['name'];
        }
    }

    $row = (object) ['id' => 1, 'name' => 'Jan'];

    $table = new class($row) extends BaseTable
    {
        protected string $defaultEditIcon = '<svg id="custom-edit-icon"></svg>';

        protected bool $defaultActions = true;

        protected bool $defaultActionEdit = true;

        protected bool $defaultActionDelete = false;

        public function __construct(private object $row)
        {
            $this->model = DefaultIconModel::class;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(1);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make([$this->row]));

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $editAction = collect($viewData['rowActionsMap']['1'])->first(fn ($a) => $a->label === 'Edytuj');

    expect($editAction)->not->toBeNull();
    expect($editAction->icon)->toBe('<svg id="custom-edit-icon"></svg>');
});

it('default delete action in rowActionsMap uses $defaultDeleteIcon', function () {
    if (! class_exists('DefaultDeleteIconModel')) {
        class DefaultDeleteIconModel extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'default_delete_icon_models';
            protected $fillable = ['name'];
        }
    }

    $row = (object) ['id' => 2, 'name' => 'Anna'];

    $table = new class($row) extends BaseTable
    {
        protected string $defaultDeleteIcon = '<svg id="custom-delete-icon"></svg>';

        protected bool $defaultActions = true;

        protected bool $defaultActionEdit = false;

        protected bool $defaultActionDelete = true;

        public function __construct(private object $row)
        {
            $this->model = DefaultDeleteIconModel::class;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(1);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make([$this->row]));

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $deleteAction = collect($viewData['rowActionsMap']['2'])->first(fn ($a) => $a->label === 'Usuń');

    expect($deleteAction)->not->toBeNull();
    expect($deleteAction->icon)->toBe('<svg id="custom-delete-icon"></svg>');
});

// ===========================================================================
// 3.7 – preloadSubRows()
// ===========================================================================

it('preloadSubRows defaults to empty hook', function () {
    $table = makeNewFeaturesTable();
    $method = new ReflectionMethod($table, 'preloadSubRows');

    expect($method->isProtected())->toBeTrue();

    // Default implementation does nothing – no exception
    $method->invoke($table, Collection::make([]));
    expect(true)->toBeTrue();
});

it('preloadSubRows is called before iterating subRows when expandable', function () {
    $row = (object) ['id' => 1, 'name' => 'Jan'];

    $table = new class($row) extends BaseTable
    {
        public bool $preloadCalled = false;

        public ?Collection $preloadedItems = null;

        protected bool $expandable = true;

        public function __construct(private object $row) {}

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(1);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make([$this->row]));

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }

        protected function preloadSubRows(\Illuminate\Support\Collection $items): void
        {
            $this->preloadCalled = true;
            $this->preloadedItems = $items;
        }
    };

    $table->mount();
    $table->render();

    expect($table->preloadCalled)->toBeTrue();
    expect($table->preloadedItems)->not->toBeNull();
    expect($table->preloadedItems)->toHaveCount(1);
});

it('preloadSubRows is NOT called when expandable is false', function () {
    $row = (object) ['id' => 1, 'name' => 'Jan'];

    $table = new class($row) extends BaseTable
    {
        public bool $preloadCalled = false;

        protected bool $expandable = false;

        public function __construct(private object $row) {}

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(1);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make([$this->row]));

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }

        protected function preloadSubRows(\Illuminate\Support\Collection $items): void
        {
            $this->preloadCalled = true;
        }
    };

    $table->mount();
    $table->render();

    expect($table->preloadCalled)->toBeFalse();
});

// ===========================================================================
// 3.8 – staticRowActions()
// ===========================================================================

it('staticRowActions defaults to empty array', function () {
    $table = makeNewFeaturesTable();
    $method = new ReflectionMethod($table, 'staticRowActions');

    expect($method->isProtected())->toBeTrue();
    expect($method->invoke($table))->toBe([]);
});

it('staticRowActions are merged with per-row rowActions in rowActionsMap', function () {
    $rows = [
        (object) ['id' => 1, 'name' => 'Jan'],
        (object) ['id' => 2, 'name' => 'Anna'],
    ];

    $table = new class($rows) extends BaseTable
    {
        public function __construct(private array $rows) {}

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

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }

        protected function staticRowActions(): array
        {
            return [RowAction::make('Statyczna')->method('staticMethod')];
        }

        public function rowActions(mixed $row): array
        {
            return [RowAction::make("Dynamiczna {$row->id}")->method('dynamicMethod')];
        }
    };

    $table->mount();
    $viewData = $table->render()->getData();

    // Each row should have both static and dynamic actions
    expect($viewData['rowActionsMap']['1'])->toHaveCount(2);
    expect($viewData['rowActionsMap']['2'])->toHaveCount(2);

    $labels1 = array_column($viewData['rowActionsMap']['1'], 'label');
    expect($labels1)->toContain('Statyczna');
    expect($labels1)->toContain('Dynamiczna 1');

    $labels2 = array_column($viewData['rowActionsMap']['2'], 'label');
    expect($labels2)->toContain('Statyczna');
    expect($labels2)->toContain('Dynamiczna 2');
});

it('staticRowActions appear before rowActions in the actions list', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];

    $table = new class($rows) extends BaseTable
    {
        public function __construct(private array $rows) {}

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(1);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));

            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }

        protected function staticRowActions(): array
        {
            return [RowAction::make('Pierwsza')->method('first')];
        }

        public function rowActions(mixed $row): array
        {
            return [RowAction::make('Druga')->method('second')];
        }
    };

    $table->mount();
    $viewData = $table->render()->getData();
    $actions = $viewData['rowActionsMap']['1'];

    expect($actions[0]->label)->toBe('Pierwsza');
    expect($actions[1]->label)->toBe('Druga');
});