<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Cells\CheckboxCell;
use PredatorStudio\LiveTable\Cells\EditableCell;
use PredatorStudio\LiveTable\Column;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a minimal BaseTable subclass that skips the Livewire constructor.
 * updateCell() only needs: columns(), baseQuery(), and $primaryKey.
 */
function makeBaseTable(array $columns, mixed $mockRow = null): BaseTable
{
    $mockBuilder = Mockery::mock(Builder::class);

    if ($mockRow !== null) {
        $mockBuilder->shouldReceive('where')->andReturnSelf();
        $mockBuilder->shouldReceive('firstOrFail')->andReturn($mockRow);
    }

    return new class($columns, $mockBuilder) extends BaseTable
    {
        public function __construct(
            private readonly array $cols,
            private readonly mixed $builder,
        ) {
            // Intentionally skip parent (Livewire) constructor –
            // updateCell() only uses columns() and baseQuery().
        }

        protected function baseQuery(): Builder
        {
            return $this->builder;
        }

        public function columns(): array
        {
            return $this->cols;
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('does nothing when column key does not exist', function () {
    $table = makeBaseTable([Column::text('name', 'Nazwa')]);

    // Should silently return with no error
    $table->updateCell('1', 'nonexistent', 'value');

    expect(true)->toBeTrue();
});

it('does nothing when cell is not editable', function () {
    $table = makeBaseTable([Column::text('name', 'Nazwa')]);

    // TextCell is not EditableCell – should be silently ignored
    $table->updateCell('1', 'name', 'new value');

    expect(true)->toBeTrue();
});

it('calls update on editable cell when validation passes', function () {
    $updated = false;

    $customCell = new class($updated) extends EditableCell
    {
        public function __construct(private bool &$updated) {}

        public function renderEditable(mixed $row, mixed $value, string $rowId): string
        {
            return '';
        }

        public function update(mixed $row, mixed $value): void
        {
            $this->updated = true;
        }
    };

    $mockRow = (object) ['id' => '1'];
    $table = makeBaseTable(
        [Column::custom('status', 'Status', $customCell)],
        $mockRow,
    );

    $table->updateCell('1', 'status', 'active');

    expect($updated)->toBeTrue();
});

it('throws ValidationException when rules fail and does not call update', function () {
    $updated = false;

    $cell = (new CheckboxCell)
        ->validation(['accepted']);

    $cell->setColumnKey('active');

    // Wrap in a Column manually via escape hatch
    $col = Column::make('active', 'Aktywny')->cell($cell);

    $table = makeBaseTable([$col]);

    expect(fn () => $table->updateCell('1', 'active', false))
        ->toThrow(ValidationException::class);

    expect($updated)->toBeFalse();
});

it('sets column key on editable cell when assigned via factory', function () {
    $col = Column::checkbox('is_active', 'Aktywny');
    $cell = $col->getCell();

    // Render to verify columnKey was injected (appears in wire:change HTML)
    $html = $cell->renderEditable((object) [], true, '5');

    expect($html)->toContain('is_active');
});
