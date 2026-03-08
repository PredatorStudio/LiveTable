<?php

use PredatorStudio\LiveTable\Column;
use Illuminate\Database\Eloquent\Builder;

it('initializes column order on mount', function () {
    $table = new class extends \PredatorStudio\LiveTable\BaseTable {
        protected function baseQuery(): Builder
        {
            return \Mockery::mock(Builder::class);
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
