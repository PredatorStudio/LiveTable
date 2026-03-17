<?php

namespace PredatorStudio\LiveTable\Concerns;

use PredatorStudio\LiveTable\ValueObjects\FieldDefinition;

trait ManagesEditing
{
    /**
     * Return the Blade view name to use as the editing modal.
     * When null (default), the built-in modal-editing partial is rendered.
     * Override in your table class to render your own Livewire component or Blade view.
     *
     * The custom view receives the same variables as the parent view, so you can
     * use $showEditingModal, $editingFields, $editingData, $editingId, etc. directly.
     *
     * Example:
     *   protected function editingModalView(): ?string
     *   {
     *       return 'my-app::modals.edit-user';
     *   }
     */
    protected function editingModalView(): ?string
    {
        return null;
    }

    /**
     * Open the editing modal and pre-populate it with the record's current data.
     * Does nothing when $defaultActions / $defaultActionEdit is disabled or $model not set.
     */
    public function openEditingModal(string $id): void
    {
        if (! $this->defaultActions || ! $this->defaultActionEdit) {
            return;
        }

        if (! $this->hasModel()) {
            return;
        }

        $record = $this->baseQuery()->where($this->primaryKey, $id)->firstOrFail();

        $allowedKeys = array_column($this->creatingFields(), 'key');

        $this->editingData = collect($allowedKeys)
            ->mapWithKeys(fn (string $key) => [$key => data_get($record, $key) ?? ''])
            ->all();

        $this->editingId = $id;
        $this->showEditingModal = true;
    }

    /**
     * Validation rules for the editing form.
     *
     * @return array<string, string>
     */
    public function editingRules(): array
    {
        return collect($this->creatingFields())
            ->mapWithKeys(fn (FieldDefinition $f) => ["editingData.{$f->key}" => 'required'])
            ->all();
    }

    /**
     * Called before the record is updated.
     *
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(mixed $record, array &$data): void {}

    /**
     * Called after the record has been successfully updated.
     */
    protected function afterUpdate(mixed $record): void {}

    /**
     * Validate, call hooks and persist changes to the edited record.
     */
    public function updateRecord(): void
    {
        if (! $this->defaultActions || $this->editingId === '') {
            return;
        }

        $this->validate($this->editingRules());

        $record = $this->baseQuery()->where($this->primaryKey, $this->editingId)->firstOrFail();

        $this->authorizeAction('update', $record);

        $allowedKeys = array_column($this->creatingFields(), 'key');
        $data = array_intersect_key($this->editingData, array_flip($allowedKeys));

        $data = $this->hashPasswordFields($data);

        $this->beforeUpdate($record, $data);
        $record->update($data);
        $this->afterUpdate($record);

        $this->showEditingModal = false;
        $this->editingData = [];
        $this->editingId = '';

        $this->dispatch('live-table-notify', message: 'Rekord zaktualizowany.', type: 'success');
    }
}