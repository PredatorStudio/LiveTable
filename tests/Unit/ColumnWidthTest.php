<?php

use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

function makeWidthTable(): BaseTable
{
    return new class extends BaseTable
    {
        public function __construct()
        {
            // Skip Livewire constructor
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [
                Column::make('name', 'Nazwa'),
                Column::make('email', 'Email'),
            ];
        }
    };
}

it('saveColumnWidth stores the width for a valid column', function () {
    $table = makeWidthTable();
    $table->mount();

    $table->saveColumnWidth('name', 200);

    expect($table->columnWidths)->toBe(['name' => 200]);
});

it('saveColumnWidth ignores unknown column keys', function () {
    $table = makeWidthTable();
    $table->mount();

    $table->saveColumnWidth('injected; DROP TABLE', 200);

    expect($table->columnWidths)->toBe([]);
});

it('saveColumnWidth enforces minimum width of 50px', function () {
    $table = makeWidthTable();
    $table->mount();

    $table->saveColumnWidth('name', 10);

    expect($table->columnWidths['name'])->toBe(50);
});

it('saveColumnWidth can store widths for multiple columns', function () {
    $table = makeWidthTable();
    $table->mount();

    $table->saveColumnWidth('name', 150);
    $table->saveColumnWidth('email', 300);

    expect($table->columnWidths)->toBe(['name' => 150, 'email' => 300]);
});

it('saveColumnWidth overwrites existing width for the same column', function () {
    $table = makeWidthTable();
    $table->mount();

    $table->saveColumnWidth('name', 150);
    $table->saveColumnWidth('name', 250);

    expect($table->columnWidths['name'])->toBe(250);
});