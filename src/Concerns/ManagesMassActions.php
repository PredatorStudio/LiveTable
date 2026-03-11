<?php

namespace PredatorStudio\LiveTable\Concerns;

trait ManagesMassActions
{
    /**
     * Open the mass-edit modal and initialise form fields with empty values.
     * Does nothing when guards fail (massEdit off, nothing selected, model missing).
     */
    public function openMassEditModal(): void
    {
        if (! $this->massEdit || ! $this->selectable) {
            return;
        }

        if (! $this->selectAllQuery && empty($this->selected)) {
            return;
        }

        if ($this->model === '' || ! class_exists($this->model)) {
            return;
        }

        $this->massEditData = collect($this->creatingFields())
            ->mapWithKeys(fn(array $f) => [$f['key'] => ''])
            ->all();

        $this->showMassEditModal = true;
    }

    /**
     * Validation rules for the mass-edit form.
     * Defaults to empty (no required rules – fields are optional by design).
     * Override to add custom rules.
     *
     * @return array<string, string>
     */
    public function massEditRules(): array
    {
        return [];
    }

    /**
     * Called before mass-edit update.
     * Modify $data by reference to change what gets persisted.
     * Throw an exception to abort.
     *
     * @param  array<int|string>     $ids
     * @param  array<string, mixed>  $data  Non-empty field values to apply.
     */
    protected function beforeMassEdit(array $ids, array &$data): void {}

    /**
     * Called after a successful mass-edit update.
     *
     * @param  array<int|string>  $ids
     */
    protected function afterMassEdit(array $ids): void {}

    /**
     * Validate, filter empty fields and apply changes to all selected (or queried) rows.
     * Uses a single UPDATE … WHERE IN statement for efficiency.
     */
    public function massEditUpdate(): void
    {
        if (! $this->massEdit) {
            return;
        }

        $this->validate($this->massEditRules());

        // Only fillable keys, only non-empty values
        $allowedKeys = array_column($this->creatingFields(), 'key');
        $data        = array_filter(
            array_intersect_key($this->massEditData, array_flip($allowedKeys)),
            fn($v) => $v !== '' && $v !== null,
        );

        // Nothing to change – close modal and bail
        if (empty($data)) {
            $this->showMassEditModal = false;
            $this->massEditData      = [];
            return;
        }

        $this->authorizeAction('massEdit');

        // Resolve IDs
        if ($this->selectAllQuery) {
            $ids = $this->buildQuery()->pluck($this->primaryKey)->all();
        } else {
            $ids = $this->selected;
        }

        $this->beforeMassEdit($ids, $data);

        $this->baseQuery()->whereIn($this->primaryKey, $ids)->update($data);

        $this->afterMassEdit($ids);

        $this->showMassEditModal = false;
        $this->massEditData      = [];
        $this->selected          = [];
        $this->selectAllQuery    = false;
        $this->page              = 1;

        $this->dispatch('live-table-notify', message: 'Wiersze zaktualizowane.', type: 'success');
    }

    /**
     * Delete selected rows (or all filtered rows when $selectAllQuery is true).
     */
    public function massDelete(): void
    {
        if (! $this->selectable || (! $this->selectAllQuery && empty($this->selected))) {
            return;
        }

        $this->authorizeAction('massDelete');

        $query = $this->buildQuery();

        if (! $this->selectAllQuery) {
            $query->whereIn($this->primaryKey, $this->selected);
        }

        $ids = $this->selectAllQuery
            ? $query->pluck($this->primaryKey)->all()
            : $this->selected;

        $this->beforeMassDelete($ids);

        $query->delete();

        $this->afterMassDelete($ids);

        $this->selected       = [];
        $this->selectAllQuery = false;
        $this->page           = 1;

        $this->dispatch('live-table-notify', message: 'Wiersze usunięte.', type: 'success');
    }

    /**
     * Called before mass deletion. Throw an exception to abort the operation.
     *
     * @param  array<int|string>  $ids
     */
    protected function beforeMassDelete(array $ids): void {}

    /**
     * Called only after a successful mass deletion.
     *
     * @param  array<int|string>  $ids
     */
    protected function afterMassDelete(array $ids): void {}
}