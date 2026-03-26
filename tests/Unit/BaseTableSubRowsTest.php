<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use PredatorStudio\LiveTable\RowAction;
use PredatorStudio\LiveTable\SubRows;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeExpandableTable(bool $expandable, array $rows, ?array $subRowsData = null): BaseTable
{
    return new class($expandable, $rows, $subRowsData) extends BaseTable
    {
        public function __construct(
            bool $exp,
            private readonly array $rows,
            private readonly ?array $subRowsData,
        ) {
            $this->expandable = $exp;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));

            return $builder;
        }

        public function columns(): array
        {
            return [
                Column::make('id', 'ID'),
                Column::make('name', 'Nazwa'),
                Column::make('project', 'Projekt'),
            ];
        }

        protected function subRows(mixed $row): ?SubRows
        {
            if ($this->subRowsData === null) {
                return null;
            }

            $id = data_get($row, 'id');

            return isset($this->subRowsData[$id])
                ? SubRows::fromArray($this->subRowsData[$id])
                : SubRows::fromArray([]);
        }
    };
}

// ---------------------------------------------------------------------------
// Tests: expandable = false (default)
// ---------------------------------------------------------------------------

it('subRowsMap is empty when expandable is false', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Jan']];
    $table = makeExpandableTable(expandable: false, rows: $rows);
    $table->mount();

    $view = $table->render();
    $data = $view->getData();

    expect($data['subRowsMap'])->toBe([])
        ->and($data['expandable'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// Tests: expandable = true
// ---------------------------------------------------------------------------

it('builds subRowsMap keyed by primary key', function () {
    $rows = [
        (object) ['id' => 1, 'name' => 'Anna'],
        (object) ['id' => 2, 'name' => 'Bartek'],
    ];
    $subRowsData = [
        1 => [['id' => null, 'name' => null, 'project' => 'Alpha']],
        2 => [],
    ];

    $table = makeExpandableTable(expandable: true, rows: $rows, subRowsData: $subRowsData);
    $table->mount();

    $view = $table->render();
    $data = $view->getData();

    expect($data['expandable'])->toBeTrue()
        ->and($data['subRowsMap'])->toHaveKey('1')
        ->and($data['subRowsMap'])->toHaveKey('2')
        ->and($data['subRowsMap']['1'])->toHaveCount(1)
        ->and($data['subRowsMap']['2'])->toHaveCount(0);
});

it('handles null returned from subRows()', function () {
    $rows = [(object) ['id' => 5, 'name' => 'X']];

    $table = makeExpandableTable(expandable: true, rows: $rows, subRowsData: null);
    $table->mount();

    $view = $table->render();
    $data = $view->getData();

    expect($data['subRowsMap']['5'])->toBe([]);
});

it('subRowsMap is empty array when rows are empty and expandable', function () {
    $table = makeExpandableTable(expandable: true, rows: [], subRowsData: []);
    $table->mount();

    $view = $table->render();
    $data = $view->getData();

    expect($data['subRowsMap'])->toBe([]);
});

it('passes expandable flag to view', function () {
    $table = makeExpandableTable(expandable: true, rows: [], subRowsData: []);
    $table->mount();

    $data = $table->render()->getData();

    expect($data['expandable'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// Tests: subrows – checkboxes
// ---------------------------------------------------------------------------

it('subRowsSelectable can be enabled', function () {
    $rows = [(object) ['id' => 1, 'name' => 'Anna']];
    $table = new class(true, $rows, [1 => []]) extends BaseTable
    {
        public function __construct(bool $exp, private readonly array $rows, private readonly array $subRowsData)
        {
            $this->expandable = $exp;
            $this->subRowsSelectable = true;
        }

        protected function baseQuery(): Builder
        {
            $b = Mockery::mock(Builder::class);
            $b->shouldReceive('count')->andReturn(1);
            $b->shouldReceive('orderBy')->andReturnSelf();
            $b->shouldReceive('limit')->andReturnSelf();
            $b->shouldReceive('offset')->andReturnSelf();
            $b->shouldReceive('get')->andReturn(Collection::make($this->rows));

            return $b;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        protected function subRows(mixed $row): ?SubRows
        {
            return SubRows::fromArray($this->subRowsData[data_get($row, 'id')] ?? []);
        }
    };
    $table->mount();

    $data = $table->render()->getData();

    expect($data['subRowsSelectable'])->toBeTrue();
});

it('toggleSelectSubRow adds id to selectedSubRows', function () {
    $table = makeExpandableTable(expandable: true, rows: [], subRowsData: []);
    $table->mount();

    $table->toggleSelectSubRow('42');

    expect($table->selectedSubRows)->toContain('42');
});

it('toggleSelectSubRow removes id when already selected', function () {
    $table = makeExpandableTable(expandable: true, rows: [], subRowsData: []);
    $table->mount();

    $table->toggleSelectSubRow('42');
    $table->toggleSelectSubRow('42');

    expect($table->selectedSubRows)->not->toContain('42');
});

// ---------------------------------------------------------------------------
// Tests: subrows – actions
// ---------------------------------------------------------------------------

it('subRowActionsMap is populated when subRowsHasActions is true', function () {
    $subItem = (object) ['id' => 10, 'name' => 'Sub Anna'];
    $rows = [(object) ['id' => 1, 'name' => 'Anna']];

    $table = new class(true, $rows, [1 => [$subItem]]) extends BaseTable
    {
        public function __construct(bool $exp, private readonly array $rows, private readonly array $subRowsData)
        {
            $this->expandable = $exp;
            $this->subRowsHasActions = true;
        }

        protected function baseQuery(): Builder
        {
            $b = Mockery::mock(Builder::class);
            $b->shouldReceive('count')->andReturn(1);
            $b->shouldReceive('orderBy')->andReturnSelf();
            $b->shouldReceive('limit')->andReturnSelf();
            $b->shouldReceive('offset')->andReturnSelf();
            $b->shouldReceive('get')->andReturn(Collection::make($this->rows));

            return $b;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        protected function subRows(mixed $row): ?SubRows
        {
            return SubRows::fromArray($this->subRowsData[data_get($row, 'id')] ?? []);
        }

        public function subRowActions(mixed $subRow): array
        {
            return [RowAction::make('Usuń')->method('deleteSubRow')];
        }
    };
    $table->mount();

    $data = $table->render()->getData();

    expect($data['subRowActionsMap'])->toHaveKey('1')
        ->and($data['subRowActionsMap']['1'])->toHaveKey('10')
        ->and($data['subRowActionsMap']['1']['10'])->toHaveCount(1)
        ->and($data['subRowActionsMap']['1']['10'][0]->label)->toBe('Usuń');
});

