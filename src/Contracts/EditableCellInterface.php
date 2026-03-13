<?php

namespace PredatorStudio\LiveTable\Contracts;

interface EditableCellInterface extends PlainRenderableCellInterface
{
    /**
     * Render the editable input element with Livewire wiring.
     *
     * @param  string  $rowId  Primary key value of the row (string form).
     */
    public function renderEditable(mixed $row, mixed $value, string $rowId): string;

    /**
     * Persist the new value for the given row.
     */
    public function update(mixed $row, mixed $value): void;

    /**
     * Validate the incoming value before persisting.
     * Should throw a ValidationException on failure.
     */
    public function validate(mixed $value): void;
}