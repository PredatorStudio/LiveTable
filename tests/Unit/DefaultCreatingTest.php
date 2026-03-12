<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Stub model classes (guarded to avoid redefinition across test runs)
// ---------------------------------------------------------------------------

if (! class_exists('FakeCreatingModel')) {
    class FakeCreatingModel extends Model
    {
        protected $table = 'fake_creating_models';

        protected $fillable = ['name', 'email', 'age', 'active', 'bio', 'password'];

        protected $casts = [
            'age' => 'integer',
            'active' => 'boolean',
        ];

        /** Override to avoid real DB insert in unit tests. */
        public static function create(array $attributes = []): static
        {
            return new static($attributes);
        }
    }
}

if (! class_exists('FakeEmptyFillableModel')) {
    class FakeEmptyFillableModel extends Model
    {
        protected $table = 'fake_empty';

        protected $fillable = [];

        public static function create(array $attributes = []): static
        {
            return new static($attributes);
        }
    }
}

// ---------------------------------------------------------------------------
// Helper – minimal BaseTable stub for creating tests
// ---------------------------------------------------------------------------

function makeCreatingTable(
    string $model = '',
    bool $defaultCreating = true,
    array $extraCreatingData = [],
): BaseTable {
    return new class($model, $defaultCreating, $extraCreatingData) extends BaseTable
    {
        public bool $beforeCalled = false;

        public bool $afterCalled = false;

        public array $receivedData = [];

        public mixed $receivedRecord = null;

        public function __construct(
            string $modelClass,
            bool $dc,
            private array $extraCreatingData,
        ) {
            $this->model = $modelClass;
            $this->defaultCreating = $dc;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        /** Override Livewire's validate() to be a no-op in unit tests. */
        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return $this->creatingData;
        }

        protected function beforeCreate(array &$data): void
        {
            $this->beforeCalled = true;
            $this->receivedData = $data;
            foreach ($this->extraCreatingData as $k => $v) {
                $data[$k] = $v;
            }
        }

        protected function afterCreate(mixed $record): void
        {
            $this->afterCalled = true;
            $this->receivedRecord = $record;
        }
    };
}

// ---------------------------------------------------------------------------
// Property defaults
// ---------------------------------------------------------------------------

it('defaultCreating defaults to false', function () {
    $table = new class extends BaseTable
    {
        public function __construct() {}

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };
    $prop = new ReflectionProperty($table, 'defaultCreating');
    expect($prop->getValue($table))->toBeFalse();
});

it('model defaults to empty string', function () {
    $table = makeCreatingTable();
    $prop = new ReflectionProperty($table, 'model');
    expect($prop->getValue($table))->toBe('');
});

it('showCreatingModal defaults to false', function () {
    $table = makeCreatingTable();
    expect($table->showCreatingModal)->toBeFalse();
});

it('creatingData defaults to empty array', function () {
    $table = makeCreatingTable();
    expect($table->creatingData)->toBe([]);
});

// ---------------------------------------------------------------------------
// creatingFields()
// ---------------------------------------------------------------------------

it('creatingFields returns empty when model is not set', function () {
    $table = makeCreatingTable(model: '');
    expect($table->creatingFields())->toBe([]);
});

it('creatingFields returns empty when model class does not exist', function () {
    $table = makeCreatingTable(model: 'NonExistent\\Model');
    expect($table->creatingFields())->toBe([]);
});

it('creatingFields returns empty when fillable is empty', function () {
    $table = makeCreatingTable(model: FakeEmptyFillableModel::class);
    expect($table->creatingFields())->toBe([]);
});

it('creatingFields returns one entry per fillable field', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = $table->creatingFields();
    $keys = array_column($fields, 'key');

    expect($keys)->toContain('name')
        ->and($keys)->toContain('email')
        ->and($keys)->toContain('age')
        ->and($keys)->toContain('active');
});

it('creatingFields entries have key, label and type', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = $table->creatingFields();

    expect($fields[0])->toHaveKeys(['key', 'label', 'type']);
});

it('creatingFields generates headline label from key', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = collect($table->creatingFields())->keyBy('key');

    // 'name' → 'Name'
    expect($fields['name']['label'])->toBe('Name');
});

// ---------------------------------------------------------------------------
// Type detection – $casts override
// ---------------------------------------------------------------------------

it('detects number type for integer cast', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = collect($table->creatingFields())->keyBy('key');

    // 'age' cast to 'integer' → 'number'
    expect($fields['age']['type'])->toBe('number');
});

it('detects checkbox type for boolean cast', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = collect($table->creatingFields())->keyBy('key');

    // 'active' cast to 'boolean' → 'checkbox'
    expect($fields['active']['type'])->toBe('checkbox');
});

// ---------------------------------------------------------------------------
// Type detection – built-in name heuristics
// ---------------------------------------------------------------------------

it('detects email type for field named email', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['email']['type'])->toBe('email');
});

it('detects password type for field named password', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['password']['type'])->toBe('password');
});

// ---------------------------------------------------------------------------
// Type detection – config override (highest priority)
// ---------------------------------------------------------------------------

it('config creating_field_types overrides built-in heuristic', function () {
    config(['live-table.creating_field_types' => ['email' => 'text']]);

    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $fields = collect($table->creatingFields())->keyBy('key');

    // config says email → text, overrides built-in email heuristic
    expect($fields['email']['type'])->toBe('text');
});

it('config creating_field_types supports wildcard patterns', function () {
    config(['live-table.creating_field_types' => ['*_color' => 'color']]);

    if (! class_exists('FakeColorModel')) {
        eval('
            class FakeColorModel extends \Illuminate\Database\Eloquent\Model {
                protected $table    = "fake_color_models";
                protected $fillable = ["bg_color", "text_color"];
                public static function create(array $a = []): static { return new static($a); }
            }
        ');
    }

    $table = makeCreatingTable(model: 'FakeColorModel');
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['bg_color']['type'])->toBe('color')
        ->and($fields['text_color']['type'])->toBe('color');
});

// ---------------------------------------------------------------------------
// openCreatingModal()
// ---------------------------------------------------------------------------

it('openCreatingModal does nothing when defaultCreating is false', function () {
    $table = makeCreatingTable(defaultCreating: false, model: FakeCreatingModel::class);
    $table->openCreatingModal();

    expect($table->showCreatingModal)->toBeFalse();
});

it('openCreatingModal does nothing when model is not set', function () {
    $table = makeCreatingTable(model: '');
    $table->openCreatingModal();

    expect($table->showCreatingModal)->toBeFalse();
});

it('openCreatingModal sets showCreatingModal to true', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->openCreatingModal();

    expect($table->showCreatingModal)->toBeTrue();
});

it('openCreatingModal initializes creatingData with empty string per field', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->openCreatingModal();

    $keys = array_column((new FakeCreatingModel)->getFillable(), null);
    foreach ($keys as $key) {
        expect($table->creatingData)->toHaveKey($key);
    }
});

it('openCreatingModal initializes creatingData values as empty strings', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->openCreatingModal();

    foreach ($table->creatingData as $value) {
        expect($value)->toBe('');
    }
});

// ---------------------------------------------------------------------------
// createRecord()
// ---------------------------------------------------------------------------

it('createRecord does nothing when defaultCreating is false', function () {
    $table = makeCreatingTable(defaultCreating: false, model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Jan'];
    $table->showCreatingModal = true;

    $table->createRecord();

    // modal stays open = nothing happened
    expect($table->showCreatingModal)->toBeTrue();
});

it('createRecord does nothing when model is not set', function () {
    $table = makeCreatingTable(model: '');
    $table->showCreatingModal = true;

    $table->createRecord();

    expect($table->showCreatingModal)->toBeTrue();
});

it('createRecord calls beforeCreate hook', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@example.com'];

    $table->createRecord();

    expect($table->beforeCalled)->toBeTrue();
});

it('createRecord calls afterCreate hook', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@example.com'];

    $table->createRecord();

    expect($table->afterCalled)->toBeTrue();
});

it('createRecord afterCreate receives created model instance', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@example.com'];

    $table->createRecord();

    expect($table->receivedRecord)->toBeInstanceOf(FakeCreatingModel::class);
});

it('createRecord closes modal after creation', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@example.com'];
    $table->showCreatingModal = true;

    $table->createRecord();

    expect($table->showCreatingModal)->toBeFalse();
});

it('createRecord resets creatingData after creation', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@example.com'];

    $table->createRecord();

    expect($table->creatingData)->toBe([]);
});

it('createRecord resets page to 1', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@example.com'];
    $table->page = 5;

    $table->createRecord();

    expect($table->page)->toBe(1);
});

it('beforeCreate can modify data by reference', function () {
    $table = makeCreatingTable(
        model: FakeCreatingModel::class,
        extraCreatingData: ['name' => 'OVERRIDDEN'],
    );
    $table->creatingData = ['name' => 'Original', 'email' => 'a@example.com'];

    $table->createRecord();

    // The model received modified data
    expect($table->receivedRecord->name)->toBe('OVERRIDDEN');
});

it('createRecord only passes fillable keys to model', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    // Inject an extra key not in fillable
    $table->creatingData = ['name' => 'Anna', 'email' => 'a@b.com', '__injected' => 'evil'];

    $table->createRecord();

    // '__injected' should not be present in the record
    expect(isset($table->receivedRecord->__injected))->toBeFalse();
});

// ---------------------------------------------------------------------------
// creatingRules()
// ---------------------------------------------------------------------------

it('creatingRules returns required rule for each field', function () {
    $table = makeCreatingTable(model: FakeCreatingModel::class);
    $rules = $table->creatingRules();

    expect($rules)->toHaveKey('creatingData.name')
        ->and($rules['creatingData.name'])->toContain('required');
});

it('creatingRules returns empty array when no fields', function () {
    $table = makeCreatingTable(model: '');
    expect($table->creatingRules())->toBe([]);
});
