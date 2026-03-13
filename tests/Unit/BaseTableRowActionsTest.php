<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Enums\RowActionsMode;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use PredatorStudio\LiveTable\RowAction;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Defaults
// ---------------------------------------------------------------------------

it('rowActions returns empty array by default', function () {
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

    $row = (object) ['id' => 1];
    expect($table->rowActions($row))->toBe([]);
});

it('rowActionsMode defaults to DROPDOWN', function () {
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

    $prop = new ReflectionProperty($table, 'rowActionsMode');
    $prop->setAccessible(true);

    expect($prop->getValue($table))->toBe(RowActionsMode::DROPDOWN);
});

// ---------------------------------------------------------------------------
// render() – rowActionsMap and hasRowActions
// ---------------------------------------------------------------------------

function makeRowActionsTable(array $rows = [], array $actions = [], RowActionsMode $mode = RowActionsMode::DROPDOWN): BaseTable
{
    return new class($rows, $actions, $mode) extends BaseTable
    {
        public function __construct(
            private readonly array $rows,
            private readonly array $actionDefs,
            RowActionsMode $mode,
        ) {
            $this->rowActionsMode = $mode;
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

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }

        public function rowActions(mixed $row): array
        {
            return $this->actionDefs;
        }
    };
}

it('hasRowActions is false when rowActions returns empty array', function () {
    $table = makeRowActionsTable(
        rows: [(object) ['id' => 1, 'name' => 'Jan']],
        actions: [],
    );

    $table->mount();
    $viewData = $table->render()->getData();

    expect($viewData['hasRowActions'])->toBeFalse();
});

it('hasRowActions is true when rowActions returns actions', function () {
    $table = makeRowActionsTable(
        rows: [(object) ['id' => 1, 'name' => 'Jan']],
        actions: [RowAction::make('Edytuj')->method('editRow')],
    );

    $table->mount();
    $viewData = $table->render()->getData();

    expect($viewData['hasRowActions'])->toBeTrue();
});

it('rowActionsMap is keyed by primary key string', function () {
    $rows = [
        (object) ['id' => 5, 'name' => 'Jan'],
        (object) ['id' => 9, 'name' => 'Anna'],
    ];

    $table = makeRowActionsTable(
        rows: $rows,
        actions: [RowAction::make('Edytuj')->method('editRow')],
    );

    $table->mount();
    $viewData = $table->render()->getData();

    expect($viewData['rowActionsMap'])->toHaveKeys(['5', '9']);
});

it('rowActionsMap contains resolved action data per row', function () {
    $rows = [(object) ['id' => 3, 'name' => 'Jan']];

    $table = makeRowActionsTable(
        rows: $rows,
        actions: [
            RowAction::make('Edytuj')
                ->href(fn ($row) => '/edit/'.$row->id)
                ->icon('<svg/>')
                ->confirm('Edytować?'),
        ],
    );

    $table->mount();
    $viewData = $table->render()->getData();
    $action = $viewData['rowActionsMap']['3'][0];

    expect($action->label)->toBe('Edytuj')
        ->and($action->href)->toBe('/edit/3')
        ->and($action->icon)->toBe('<svg/>')
        ->and($action->confirm)->toBe('Edytować?')
        ->and($action->method)->toBe('');
});

it('rowActionsMap resolves closure href per row independently', function () {
    $rows = [
        (object) ['id' => 1, 'name' => 'Jan'],
        (object) ['id' => 2, 'name' => 'Anna'],
    ];

    $table = makeRowActionsTable(
        rows: $rows,
        actions: [RowAction::make('Edytuj')->href(fn ($row) => '/edit/'.$row->id)],
    );

    $table->mount();
    $viewData = $table->render()->getData();

    expect($viewData['rowActionsMap']['1'][0]->href)->toBe('/edit/1')
        ->and($viewData['rowActionsMap']['2'][0]->href)->toBe('/edit/2');
});

it('rowActionsMode is passed to view', function () {
    $table = makeRowActionsTable(
        rows: [(object) ['id' => 1, 'name' => 'Jan']],
        actions: [RowAction::make('Edytuj')->method('editRow')],
        mode: RowActionsMode::ICONS,
    );

    $table->mount();
    $viewData = $table->render()->getData();

    expect($viewData['rowActionsMode'])->toBe(RowActionsMode::ICONS);
});

it('colspan increases by 1 when hasRowActions is true', function () {
    $rowsWithActions = makeRowActionsTable(
        rows: [(object) ['id' => 1, 'name' => 'Jan']],
        actions: [RowAction::make('Edytuj')->method('editRow')],
    );

    $rowsWithout = makeRowActionsTable(
        rows: [(object) ['id' => 1, 'name' => 'Jan']],
        actions: [],
    );

    $rowsWithActions->mount();
    $rowsWithout->mount();

    $colspanWith = $rowsWithActions->render()->getData()['colspan'];
    $colspanWithout = $rowsWithout->render()->getData()['colspan'];

    expect($colspanWith)->toBe($colspanWithout + 1);
});
