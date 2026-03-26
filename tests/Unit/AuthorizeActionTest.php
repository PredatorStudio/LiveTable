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
// authorizeAction is called before the action and aborts on throw
// (pattern verified for deleteRow; same mechanism applies to all actions)
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

it('deleteRow is aborted when authorizeAction throws', function () {
    $record = Mockery::mock();
    $record->shouldReceive('delete')->never();

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->andReturnSelf();
    $builder->shouldReceive('firstOrFail')->andReturn($record);

    $table = makeAuthTable(query: $builder, shouldThrow: true);

    expect(fn () => $table->deleteRow('1'))->toThrow(AuthorizationException::class);
});


