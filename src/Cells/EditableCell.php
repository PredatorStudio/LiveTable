<?php

namespace PredatorStudio\LiveTable\Cells;

abstract class EditableCell extends Cell implements \PredatorStudio\LiveTable\Contracts\EditableCellInterface
{
    protected array $rules = [];

    protected string $columnKey = '';

    public function validation(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function setColumnKey(string $key): void
    {
        $this->columnKey = $key;
    }

    public function validate(mixed $value): void
    {
        if (empty($this->rules)) {
            return;
        }

        validator(['value' => $value], ['value' => $this->rules])->validate();
    }

    /**
     * Renders the editable input HTML with Livewire wiring.
     * $rowId is the primary key value of the row (string form).
     */
    abstract public function renderEditable(mixed $row, mixed $value, string $rowId): string;

    public function render(mixed $row, mixed $value): string
    {
        return $this->renderEditable($row, $value, '');
    }

    abstract public function update(mixed $row, mixed $value): void;
}
