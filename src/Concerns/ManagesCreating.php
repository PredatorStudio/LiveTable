<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PredatorStudio\LiveTable\Cells\CheckboxCell;
use PredatorStudio\LiveTable\Cells\SelectCell;
use PredatorStudio\LiveTable\ValueObjects\FieldDefinition;

trait ManagesCreating
{
    /**
     * Return the Blade view name to use as the creating modal.
     * When null (default), the built-in modal-creating partial is rendered.
     * Override in your table class to render your own Livewire component or Blade view.
     *
     * The custom view receives the same variables as the parent view, so you can
     * use $showCreatingModal, $creatingFields, $creatingData, etc. directly.
     *
     * Example:
     *   protected function creatingModalView(): ?string
     *   {
     *       return 'my-app::modals.create-user';
     *   }
     */
    protected function creatingModalView(): ?string
    {
        return null;
    }

    /**
     * Open the default-creating modal and initialise $creatingData with empty values.
     */
    public function openCreatingModal(): void
    {
        if (! $this->defaultCreating || ! $this->hasModel()) {
            return;
        }

        $this->creatingData = collect($this->creatingFields())
            ->mapWithKeys(fn (FieldDefinition $field) => [$field->key => ''])
            ->all();

        $this->showCreatingModal = true;
    }

    /**
     * Field definitions for the creating (and editing) form.
     *
     * @return FieldDefinition[]
     */
    public function creatingFields(): array
    {
        if (! $this->hasModel()) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = new $this->model;
        $fillable = $instance->getFillable();

        if (empty($fillable)) {
            return [];
        }

        // Apply $creatableFields whitelist when configured
        if (! empty($this->creatableFields)) {
            $fillable = array_values(array_intersect($fillable, $this->creatableFields));
        }

        if (empty($fillable)) {
            return [];
        }

        $casts = $instance->getCasts();
        $table = $instance->getTable();

        // Build key → cell map from column definitions for type overrides
        $columnCells = collect($this->columns())
            ->keyBy(fn ($col) => $col->key)
            ->map(fn ($col) => $col->getCell())
            ->all();

        return array_map(function (string $key) use ($casts, $table, $columnCells) {
            $type = $this->resolveFieldType($key, $casts, $table);
            $options = [];

            $cell = $columnCells[$key] ?? null;

            if ($cell instanceof SelectCell) {
                $type = 'select';
                $options = $cell->getOptions();
            } elseif ($cell instanceof CheckboxCell) {
                $type = 'checkbox';
            }

            return new FieldDefinition($key, Str::headline($key), $type, $options);
        }, $fillable);
    }

    /**
     * Validation rules for the creating form.
     *
     * @return array<string, string>
     */
    public function creatingRules(): array
    {
        return collect($this->creatingFields())
            ->mapWithKeys(fn (FieldDefinition $f) => ["creatingData.{$f->key}" => 'required'])
            ->all();
    }

    /**
     * Called before the model is created.
     *
     * @param  array<string, mixed>  $data
     */
    protected function beforeCreate(array &$data): void {}

    /**
     * Called after the model has been successfully created.
     */
    protected function afterCreate(mixed $record): void {}

    /**
     * Validate the creating form, call hooks, persist the record and close the modal.
     */
    public function createRecord(): void
    {
        if (! $this->defaultCreating || ! $this->hasModel()) {
            return;
        }

        $this->validate($this->creatingRules());

        $allowedKeys = array_column($this->creatingFields(), 'key');
        $data = array_intersect_key($this->creatingData, array_flip($allowedKeys));

        $this->authorizeAction('create');

        $data = $this->hashPasswordFields($data);

        $this->beforeCreate($data);

        $record = ($this->model)::create($data);

        $this->afterCreate($record);

        $this->showCreatingModal = false;
        $this->creatingData = [];
        $this->page = 1;

        $this->dispatch('live-table-notify', message: 'Rekord dodany.', type: 'success');
    }

    // -------------------------------------------------------------------------
    // Field type resolution (private – used only by creatingFields())
    // -------------------------------------------------------------------------

    /**
     * Resolve the HTML input type for a given fillable field.
     *
     * Priority (lowest → highest):
     *   1. DB schema type
     *   2. Model $casts override
     *   3. Built-in name heuristics (email, password, url)
     *   4. config('live-table.creating_field_types') – highest priority
     */
    private function resolveFieldType(string $key, array $casts, string $table): string
    {
        // 1. Base type from DB schema
        $type = $this->typeFromSchema($table, $key);

        // 2. Override with $casts
        if (isset($casts[$key])) {
            $castBase = strtolower(explode(':', (string) $casts[$key])[0]);
            $fromCast = match (true) {
                in_array($castBase, ['int', 'integer', 'float', 'double', 'decimal']) => 'number',
                in_array($castBase, ['bool', 'boolean']) => 'checkbox',
                $castBase === 'date' => 'date',
                in_array($castBase, ['datetime', 'timestamp']) => 'datetime-local',
                default => null,
            };
            if ($fromCast !== null) {
                $type = $fromCast;
            }
        }

        // 3. Built-in name heuristics
        if (Str::is(['password', '*_password'], $key)) {
            $type = 'password';
        } elseif (Str::is(['email', '*_email'], $key)) {
            $type = 'email';
        } elseif (Str::is(['url', '*_url', 'website'], $key)) {
            $type = 'url';
        }

        // 4. Config overrides – highest priority
        foreach (config('live-table.creating_field_types', []) as $pattern => $inputType) {
            if (Str::is($pattern, $key)) {
                return $inputType;
            }
        }

        return $type;
    }

    /**
     * Determine the HTML input type from the DB schema column type.
     * Results are cached statically per request to avoid repeated queries.
     */
    private function typeFromSchema(string $table, string $column): string
    {
        $cacheKey = "{$table}.{$column}";

        if (array_key_exists($cacheKey, self::$schemaCache)) {
            return self::$schemaCache[$cacheKey];
        }

        try {
            $dbType = Schema::getColumnType($table, $column);
        } catch (\Throwable) {
            return self::$schemaCache[$cacheKey] = 'text';
        }

        $type = match (true) {
            in_array($dbType, ['integer', 'bigint', 'smallint', 'tinyint', 'int',
                'float', 'double', 'decimal']) => 'number',
            $dbType === 'boolean' => 'checkbox',
            $dbType === 'date' => 'date',
            in_array($dbType, ['datetime', 'timestamp']) => 'datetime-local',
            in_array($dbType, ['text', 'longtext', 'mediumtext']) => 'textarea',
            default => 'text',
        };

        return self::$schemaCache[$cacheKey] = $type;
    }
}