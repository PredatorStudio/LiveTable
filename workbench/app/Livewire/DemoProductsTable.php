<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Filter;
use Workbench\App\Models\DemoProduct;

/**
 * Demo table used in feature tests for auto-apply filter logic.
 * Does NOT override applyFilters() – relies on the default auto-apply.
 */
class DemoProductsTable extends BaseTable
{
    protected function baseQuery(): Builder
    {
        return DemoProduct::query();
    }

    public function columns(): array
    {
        return [
            Column::text('name', 'Nazwa')->sortable(),
            Column::text('category', 'Kategoria'),
            Column::money('price', 'Cena')->sortable(),
            Column::number('quantity', 'Ilość'),
            Column::checkbox('is_active', 'Aktywny'),
            Column::date('manufactured_at', 'Data produkcji'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::text('name', 'Nazwa'),
            Filter::select('category', 'Kategoria', [
                'general'     => 'Ogólne',
                'electronics' => 'Elektronika',
                'food'        => 'Żywność',
            ]),
            Filter::numberRange('quantity', 'Ilość'),
            Filter::dateRange('manufactured_at', 'Data produkcji'),
            Filter::boolean('is_active', 'Aktywny'),
            Filter::money('price', 'Cena'),
        ];
    }
}
