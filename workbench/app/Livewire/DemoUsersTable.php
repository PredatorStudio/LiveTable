<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Cells\SelectCell;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Filter;
use PredatorStudio\LiveTable\SubRows;
use Workbench\App\Models\DemoUser;

class DemoUsersTable extends BaseTable
{
    protected string $model = DemoUser::class;

    protected bool $selectable = true;

    protected bool $defaultCreating = true;

    protected bool $defaultActions = true;

    protected bool $massEdit = true;

    protected bool $massDelete = true;

    protected bool $exportCsv = true;

    protected bool $exportPdf = false;

    /**
     * Gdy true – wiersze są grupowane po imieniu; każda grupa jest rozwijalna
     * i pokazuje subwiersze z pełnymi danymi użytkowników.
     */
    protected bool $grouped = true;

    public function mount(): void
    {
        if ($this->grouped) {
            $this->expandable = true;
        }

        parent::mount();
    }

    protected function baseQuery(): Builder
    {
        if ($this->grouped) {
            return DemoUser::query()
                ->groupBy('imie');
        }

        return DemoUser::query();
    }

    public function columns(): array
    {
        if ($this->grouped) {
            return [
                Column::text('imie', 'Imię')->sortable(),
                Column::text('nazwisko', 'Nazwisko')->sortable(),
                Column::text('adres', 'Adres')->sortable(),
                Column::select('status', 'Status', SelectCell::fromArray([
                    'active' => 'Aktywny',
                    'inactive' => 'Nieaktywny',
                ]))->sortable(),
            ];
        }

        return [
            Column::text('imie', 'Imię')->sortable(),
            Column::text('nazwisko', 'Nazwisko')->sortable(),
            Column::text('adres', 'Adres')->sortable(),
            Column::select('status', 'Status', SelectCell::fromArray([
                'active' => 'Aktywny',
                'inactive' => 'Nieaktywny',
            ]))->sortable(),
        ];
    }

    protected function subRows(mixed $row): ?SubRows
    {
        if ($this->grouped) {
            return SubRows::fromQuery(
                DemoUser::query()->where('imie', $row->imie)
            );
        }

        return null;
    }

    public function filters(): array
    {
        return [
            Filter::text('imie', 'Imię'),
            Filter::text('nazwisko', 'Nazwisko'),
            Filter::select('status', 'Status', [
                'active' => 'Aktywny',
                'inactive' => 'Nieaktywny',
            ]),
        ];
    }

    public function creatingFields(): array
    {
        return [
            ['key' => 'imie',     'label' => 'Imię',     'type' => 'text'],
            ['key' => 'nazwisko', 'label' => 'Nazwisko', 'type' => 'text'],
            ['key' => 'adres',    'label' => 'Adres',    'type' => 'text'],
            ['key' => 'status',   'label' => 'Status',   'type' => 'text'],
        ];
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        if ($this->grouped) {
            return $query->where('imie', 'like', "%{$search}%");
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('imie', 'like', "%{$search}%")
                ->orWhere('nazwisko', 'like', "%{$search}%")
                ->orWhere('adres', 'like', "%{$search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        foreach ($this->activeFilters as $key => $value) {
            if ($value !== '' && $value !== null) {
                match ($key) {
                    'imie' => $query->where('imie', 'like', "%{$value}%"),
                    'nazwisko' => $query->where('nazwisko', 'like', "%{$value}%"),
                    'status' => $query->where('status', $value),
                    default => null,
                };
            }
        }

        return $query;
    }
}
