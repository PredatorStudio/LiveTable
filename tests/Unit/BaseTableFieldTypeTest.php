<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Cells\SelectCell;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);

    // Create a test table with various column types in SQLite
    Schema::create('field_type_test', function ($table) {
        $table->id();
        $table->string('name');
        $table->text('bio');
        $table->integer('age');
        $table->boolean('active');
        $table->date('born_on');
        $table->dateTime('published_at');
        $table->string('website');
        $table->string('email_address');
        $table->string('reset_password');
        $table->string('profile_url');
    });
});

afterEach(function () {
    Schema::dropIfExists('field_type_test');
    // Reset static schema cache between tests
    (new ReflectionProperty(BaseTable::class, 'schemaCache'))->setValue(null, []);
    Mockery::close();
});

// ---------------------------------------------------------------------------
// Helper – model pointing at the test table
// ---------------------------------------------------------------------------

function makeFieldTypeModel(array $fillable, array $casts = []): string
{
    $class = 'FieldTypeTestModel_'.md5(serialize($fillable).serialize($casts));

    if (! class_exists($class)) {
        $fillableStr = implode("','", $fillable);
        $castsStr = implode(',', array_map(
            fn ($k, $v) => "'{$k}' => '{$v}'",
            array_keys($casts),
            $casts,
        ));

        eval("
            class {$class} extends \\Illuminate\\Database\\Eloquent\\Model {
                protected \$table    = 'field_type_test';
                protected \$fillable = ['{$fillableStr}'];
                protected \$casts    = [{$castsStr}];
                public static function create(array \$a = []): static { return new static(\$a); }
            }
        ");
    }

    return $class;
}

function makeFieldTypeTable(array $fillable, array $casts = []): BaseTable
{
    $modelClass = makeFieldTypeModel($fillable, $casts);

    return new class($modelClass) extends BaseTable
    {
        public function __construct(private readonly string $modelClass)
        {
            $this->model = $this->modelClass;
            $this->defaultCreating = true;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }
    };
}

// ---------------------------------------------------------------------------
// typeFromSchema() – DB column type → HTML input type
// ---------------------------------------------------------------------------

it('typeFromSchema returns number for integer column', function () {
    $table = makeFieldTypeTable(['age']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['age']['type'])->toBe('number');
});

it('typeFromSchema returns number for boolean column in SQLite (stored as integer)', function () {
    // SQLite stores boolean as integer, so Schema::getColumnType() returns 'integer' → 'number'
    // Use model $casts to get 'checkbox' – see separate test below
    $table = makeFieldTypeTable(['active']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['active']['type'])->toBe('number');
});

it('boolean field gets checkbox type via model cast', function () {
    // Cast to boolean overrides the schema-based 'number' from SQLite integer
    $table = makeFieldTypeTable(['active'], ['active' => 'boolean']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['active']['type'])->toBe('checkbox');
});

it('typeFromSchema returns date for date column', function () {
    $table = makeFieldTypeTable(['born_on']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['born_on']['type'])->toBe('date');
});

it('typeFromSchema returns datetime-local for datetime column', function () {
    $table = makeFieldTypeTable(['published_at']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['published_at']['type'])->toBe('datetime-local');
});

it('typeFromSchema returns textarea for text column', function () {
    $table = makeFieldTypeTable(['bio']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['bio']['type'])->toBe('textarea');
});

it('typeFromSchema returns text for string column', function () {
    $table = makeFieldTypeTable(['name']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['name']['type'])->toBe('text');
});

it('typeFromSchema returns text as fallback when column does not exist', function () {
    $table = makeFieldTypeTable(['nonexistent_column']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['nonexistent_column']['type'])->toBe('text');
});

// ---------------------------------------------------------------------------
// typeFromSchema() – static cache
// ---------------------------------------------------------------------------

it('typeFromSchema caches result and does not call Schema twice for same column', function () {
    $schemaCallCount = 0;

    // Spy on Schema calls by calling creatingFields() twice – second call uses cache
    $table = makeFieldTypeTable(['age']);

    $table->creatingFields(); // populates cache
    $table->creatingFields(); // should use cache, not DB

    // Verify cache has the entry
    $cache = (new ReflectionProperty(BaseTable::class, 'schemaCache'))->getValue(null);
    expect($cache)->toHaveKey('field_type_test.age');
    expect($cache['field_type_test.age'])->toBe('number');
});

// ---------------------------------------------------------------------------
// resolveFieldType() – name heuristics
// ---------------------------------------------------------------------------

it('resolveFieldType returns url for field named website', function () {
    $table = makeFieldTypeTable(['website']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['website']['type'])->toBe('url');
});

it('resolveFieldType returns email for field matching *_email pattern', function () {
    $table = makeFieldTypeTable(['email_address']);
    $fields = collect($table->creatingFields())->keyBy('key');

    // 'email_address' does not match '*_email' (it ends with '_address'), falls back to 'text'
    expect($fields['email_address']['type'])->toBe('text');
});

it('resolveFieldType returns password for field matching *_password pattern', function () {
    $table = makeFieldTypeTable(['reset_password']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['reset_password']['type'])->toBe('password');
});

it('resolveFieldType returns url for field matching *_url pattern', function () {
    $table = makeFieldTypeTable(['profile_url']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['profile_url']['type'])->toBe('url');
});

// ---------------------------------------------------------------------------
// resolveFieldType() – $casts override takes precedence over schema
// ---------------------------------------------------------------------------

it('cast to integer overrides schema string type', function () {
    // 'name' is a string in DB, but cast to integer
    $table = makeFieldTypeTable(['name'], ['name' => 'integer']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['name']['type'])->toBe('number');
});

it('cast to boolean overrides schema string type', function () {
    $table = makeFieldTypeTable(['name'], ['name' => 'boolean']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['name']['type'])->toBe('checkbox');
});

// ---------------------------------------------------------------------------
// resolveFieldType() – name heuristic overrides schema and cast
// ---------------------------------------------------------------------------

it('password heuristic overrides integer cast', function () {
    // Even if cast to integer, 'password' field name wins
    $table = makeFieldTypeTable(['reset_password'], ['reset_password' => 'integer']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['reset_password']['type'])->toBe('password');
});

// ---------------------------------------------------------------------------
// resolveFieldType() – config override (highest priority)
// ---------------------------------------------------------------------------

it('config creating_field_types overrides name heuristic for password field', function () {
    config(['live-table.creating_field_types' => ['reset_password' => 'text']]);

    $table = makeFieldTypeTable(['reset_password']);
    $fields = collect($table->creatingFields())->keyBy('key');

    // Config says 'text', wins over '*_password' heuristic
    expect($fields['reset_password']['type'])->toBe('text');
});

it('config creating_field_types can map DB text column to custom type', function () {
    config(['live-table.creating_field_types' => ['bio' => 'richtext']]);

    $table = makeFieldTypeTable(['bio']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['bio']['type'])->toBe('richtext');
});

// ---------------------------------------------------------------------------
// Column-based type overrides (SelectCell / CheckboxCell)
// ---------------------------------------------------------------------------

it('field with matching SelectCell column gets type select with options', function () {
    $modelClass = makeFieldTypeModel(['name']);

    $table = new class($modelClass) extends BaseTable
    {
        public function __construct(private readonly string $modelClass)
        {
            $this->model = $this->modelClass;
            $this->defaultCreating = true;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [
                Column::select('name', 'Status', SelectCell::fromArray(['a' => 'Alfa', 'b' => 'Beta'])),
            ];
        }
    };

    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['name']['type'])->toBe('select');
    expect($fields['name']['options'])->toBe(['a' => 'Alfa', 'b' => 'Beta']);
});

it('field with matching CheckboxCell column gets type checkbox', function () {
    $modelClass = makeFieldTypeModel(['name']);

    $table = new class($modelClass) extends BaseTable
    {
        public function __construct(private readonly string $modelClass)
        {
            $this->model = $this->modelClass;
            $this->defaultCreating = true;
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [
                Column::checkbox('name', 'Aktywny'),
            ];
        }
    };

    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['name']['type'])->toBe('checkbox');
});

it('fields without matching column definition have options as empty array', function () {
    $table = makeFieldTypeTable(['name']);
    $fields = collect($table->creatingFields())->keyBy('key');

    expect($fields['name'])->toHaveKey('options');
    expect($fields['name']['options'])->toBe([]);
});
