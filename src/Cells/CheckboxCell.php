<?php

namespace PredatorStudio\LiveTable\Cells;

class CheckboxCell extends EditableCell
{
    public function renderEditable(mixed $row, mixed $value, string $rowId): string
    {
        $checked = $value ? 'checked' : '';
        $key = e($this->columnKey);
        $rowId = e($rowId);

        return <<<HTML
            <input
                type="checkbox"
                class="form-check-input"
                {$checked}
                wire:change="\$dispatch('updateCell', {rowId: '{$rowId}', columnKey: '{$key}', value: \$event.target.checked})"
            >
            HTML;
    }

    public function renderPlain(mixed $row, mixed $value): string
    {
        return $value ? '1' : '0';
    }

    public function update(mixed $row, mixed $value): void
    {
        $row->{$this->columnKey} = (bool) $value;
        $row->save();
    }
}
