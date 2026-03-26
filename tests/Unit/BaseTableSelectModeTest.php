<?php

use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Enums\SelectMode;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function selectModeTable(?SelectMode $selectMode = null): BaseTable
{
    return new class($selectMode) extends BaseTable
    {
        public function __construct(?SelectMode $mode)
        {
            $this->selectMode = $mode;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };
}

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Default / config resolution
// ---------------------------------------------------------------------------

it('mount sets selectMode from config when property is null', function () {
    config(['live-table.select_mode' => 'row']);

    $table = selectModeTable(null);
    $table->mount();

    expect((fn () => $this->selectMode)->call($table))->toBe(SelectMode::ROW);
});

it('mount falls back to SelectMode::CHECKBOX when config key is missing', function () {
    config(['live-table' => ['theme' => 'bootstrap']]); // no select_mode key

    $table = selectModeTable(null);
    $table->mount();

    expect((fn () => $this->selectMode)->call($table))->toBe(SelectMode::CHECKBOX);
});

it('mount does not override selectMode set explicitly by subclass', function () {
    config(['live-table.select_mode' => 'row']);

    $table = selectModeTable(SelectMode::CHECKBOX);
    $table->mount();

    expect((fn () => $this->selectMode)->call($table))->toBe(SelectMode::CHECKBOX);
});

