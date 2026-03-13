<?php

namespace PredatorStudio\LiveTable\Cells;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SelectCell extends EditableCell
{
    private array $options = [];

    private function __construct() {}

    public static function fromArray(array $data): static
    {
        $cell = new static;

        foreach ($data as $k => $v) {
            $cell->options[(string) $k] = (string) $v;
        }

        return $cell;
    }

    public static function fromEnum(string $enumClass): static
    {
        $cell = new static;
        $cases = $enumClass::cases();

        foreach ($cases as $case) {
            $value = $case instanceof \BackedEnum ? (string) $case->value : $case->name;
            $cell->options[$value] = $case->name;
        }

        return $cell;
    }

    public static function fromQuery(Builder|Collection $query, string $valueField = 'id', string $labelField = 'name'): static
    {
        $cell = new static;
        $results = $query instanceof Builder ? $query->get() : $query;

        foreach ($results as $item) {
            $cell->options[(string) data_get($item, $valueField)] = (string) data_get($item, $labelField);
        }

        return $cell;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns the human-readable label for the given value, or the raw value when not found.
     * Returns an empty string for null.
     */
    public function renderPlain(mixed $row, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return $this->options[(string) $value] ?? (string) $value;
    }

    public function renderEditable(mixed $row, mixed $value, string $rowId): string
    {
        $key = e($this->columnKey);
        $rowIdEsc = e($rowId);
        $current = (string) $value;

        $options = '';
        foreach ($this->options as $val => $label) {
            $selected = $val === $current ? ' selected' : '';
            $options .= '<option value="'.e($val).'"'.$selected.'>'.e($label).'</option>';
        }

        return <<<HTML
            <select
                class="form-select form-select-sm"
                wire:change="\$dispatch('updateCell', {rowId: '{$rowIdEsc}', columnKey: '{$key}', value: \$event.target.value})"
            >
                {$options}
            </select>
            HTML;
    }

    public function update(mixed $row, mixed $value): void
    {
        $row->{$this->columnKey} = $value;
        $row->save();
    }
}
