<?php

namespace PredatorStudio\LiveTable\Concerns;

trait ManagesDeletion
{
    /**
     * Called before the record is deleted. Throw an exception to abort.
     */
    protected function beforeDelete(mixed $record): void {}

    /**
     * Called after the record has been successfully deleted.
     */
    protected function afterDelete(string $id): void {}

    /**
     * Delete the row identified by $id.
     */
    public function deleteRow(string $id): void
    {
        if (! $this->defaultActions || ! $this->defaultActionDelete) {
            return;
        }

        $record = $this->baseQuery()->where($this->primaryKey, $id)->firstOrFail();

        $this->authorizeAction('delete', $record);

        $this->beforeDelete($record);
        $record->delete();
        $this->afterDelete($id);

        $this->selected = array_values(
            array_filter($this->selected, fn (string $s) => $s !== $id),
        );

        $this->dispatch('live-table-notify', message: 'Rekord usunięty.', type: 'success');
    }
}