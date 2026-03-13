<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
// Stub model
// ---------------------------------------------------------------------------

if (! class_exists('FakeAuthModel')) {
    class FakeAuthModel extends Model
    {
        protected $table = 'fake_auth_models';

        protected $fillable = ['name', 'email'];

        public static function create(array $attributes = []): static
        {
            return new static($attributes);
        }
    }
}

// ---------------------------------------------------------------------------
// Helper – table that tracks authorizeAction() calls
// ---------------------------------------------------------------------------

function makeAuthTable(
    ?Builder $query = null,
    bool $shouldThrow = false,
    string $expectedAction = '',
): BaseTable {
    return new class($query, $shouldThrow, $expectedAction) extends BaseTable
    {
        public string $capturedAction = '';

        public mixed $capturedRecord = null;

        public function __construct(
            private ?Builder $mockQuery,
            private bool $throws,
            private string $expected,
        ) {
            $this->model = FakeAuthModel::class;
            $this->defaultActions = true;
            $this->defaultActionEdit = true;
            $this->defaultActionDelete = true;
            $this->defaultCreating = true;
            $this->selectable = true;
            $this->massEdit = true;
            $this->massDelete = true;
        }

        protected function baseQuery(): Builder
        {
            return $this->mockQuery ?? Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return [];
        }

        protected function authorizeAction(string $action, mixed $record = null): void
        {
            $this->capturedAction = $action;
            $this->capturedRecord = $record;

            if ($this->throws) {
                throw new AuthorizationException('Unauthorized');
            }
        }
    };
}

// ---------------------------------------------------------------------------
// authorizeAction called before createRecord()
// ---------------------------------------------------------------------------

it('authorizeAction is called with "create" before createRecord', function () {
    $table = makeAuthTable();
    $table->creatingData = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->createRecord();

    expect($table->capturedAction)->toBe('create');
});

it('authorizeAction receives null record for create', function () {
    $table = makeAuthTable();
    $table->creatingData = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->createRecord();

    expect($table->capturedRecord)->toBeNull();
});

it('createRecord is aborted when authorizeAction throws', function () {
    $table = makeAuthTable(shouldThrow: true);
    $table->creatingData = ['name' => 'Jan', 'email' => 'j@b.com'];

    expect(fn () => $table->createRecord())->toThrow(AuthorizationException::class);

    // Modal should not be closed (action was aborted before close)
    expect($table->showCreatingModal)->toBeFalse(); // modal wasn't opened in the first place
});

// ---------------------------------------------------------------------------
// authorizeAction called before updateRecord()
// ---------------------------------------------------------------------------

it('authorizeAction is called with "update" before updateRecord', function () {
    $record = Mockery::mock();
    $record->shouldReceive('update')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder);
    $table->editingId = '1';
    $table->editingData = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->updateRecord();

    expect($table->capturedAction)->toBe('update');
});

it('authorizeAction receives the record for update', function () {
    $record = Mockery::mock();
    $record->shouldReceive('update')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder);
    $table->editingId = '5';
    $table->editingData = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->updateRecord();

    expect($table->capturedRecord)->toBe($record);
});

it('updateRecord is aborted when authorizeAction throws', function () {
    $record = Mockery::mock();
    $record->shouldReceive('update')->never();

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder, shouldThrow: true);
    $table->editingId = '1';
    $table->editingData = ['name' => 'Jan', 'email' => 'j@b.com'];
    $table->showEditingModal = true;

    expect(fn () => $table->updateRecord())->toThrow(AuthorizationException::class);
    // Modal still open – update was aborted
    expect($table->showEditingModal)->toBeTrue();
});

// ---------------------------------------------------------------------------
// authorizeAction called before deleteRow()
// ---------------------------------------------------------------------------

it('authorizeAction is called with "delete" before deleteRow', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder);
    $table->deleteRow('3');

    expect($table->capturedAction)->toBe('delete');
});

it('authorizeAction receives the record for delete', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->andReturn(true);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder);
    $table->deleteRow('7');

    expect($table->capturedRecord)->toBe($record);
});

it('deleteRow is aborted when authorizeAction throws', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->never();

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder, shouldThrow: true);

    expect(fn () => $table->deleteRow('1'))->toThrow(AuthorizationException::class);
});

// ---------------------------------------------------------------------------
// authorizeAction called before massDelete()
// ---------------------------------------------------------------------------

it('authorizeAction is called with "massDelete" before massDelete', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('delete')->andReturn(1);

    $table = makeAuthTable(query: $builder);
    $table->selected = ['1', '2'];
    $table->massDelete();

    expect($table->capturedAction)->toBe('massDelete');
});

it('massDelete is aborted when authorizeAction throws', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('delete')->never();

    $table = makeAuthTable(query: $builder, shouldThrow: true);
    $table->selected = ['1'];

    expect(fn () => $table->massDelete())->toThrow(AuthorizationException::class);
});

// ---------------------------------------------------------------------------
// authorizeAction called before massEditUpdate()
// ---------------------------------------------------------------------------

it('authorizeAction is called with "massEdit" before massEditUpdate', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereIn')->andReturnSelf();
    $builder->shouldReceive('update')->andReturn(1);

    $table = makeAuthTable(query: $builder);
    $table->selected = ['1'];
    $table->massEditData = ['name' => 'Test'];
    $table->massEditUpdate();

    expect($table->capturedAction)->toBe('massEdit');
});

it('massEditUpdate is aborted when authorizeAction throws', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('update')->never();

    $table = makeAuthTable(query: $builder, shouldThrow: true);
    $table->selected = ['1'];
    $table->massEditData = ['name' => 'Test'];

    expect(fn () => $table->massEditUpdate())->toThrow(AuthorizationException::class);
});

// ---------------------------------------------------------------------------
// Default authorizeAction() is a no-op (does not throw)
// ---------------------------------------------------------------------------

it('default authorizeAction does not throw', function () {
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

    // Should not throw – default implementation is empty
    expect(fn () => (new ReflectionMethod($table, 'authorizeAction'))->invoke($table, 'create'))->not->toThrow(\Throwable::class);
});