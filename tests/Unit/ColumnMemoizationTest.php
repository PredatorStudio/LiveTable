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

it('initializes column order on mount', function () {
    $table = new class extends BaseTable
    {
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

    $table->mount();

    expect($table->columnOrder)->toBe(['name', 'email']);
});
