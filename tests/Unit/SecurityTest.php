<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
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
// Stub helper
// ---------------------------------------------------------------------------

function securityStub(array $extraCols = []): BaseTable
{
    $cols = array_merge([
        Column::make('name', 'Nazwa')->sortable(),
        Column::make('email', 'Email'),
    ], $extraCols);

    return new class($cols) extends BaseTable
    {
        public function __construct(private readonly array $cols)
        {
            // Skip Livewire constructor
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return $this->cols;
        }
    };
}

// ===========================================================================
// 1.1 – safeSortBy() – walidacja kolumny przed orderBy()
// ===========================================================================

it('safeSortBy returns empty string when column is not in sortable list', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = 'email'; // email nie jest sortable

    $result = (new ReflectionMethod($table, 'safeSortBy'))->invoke($table);

    expect($result)->toBe('');
});

it('safeSortBy returns empty string for SQL injection attempt', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = 'name; DROP TABLE users--';

    $result = (new ReflectionMethod($table, 'safeSortBy'))->invoke($table);

    expect($result)->toBe('');
});

it('safeSortBy returns the column key when it is sortable', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = 'name'; // name jest sortable

    $result = (new ReflectionMethod($table, 'safeSortBy'))->invoke($table);

    expect($result)->toBe('name');
});

it('safeSortBy returns empty string when sortBy is empty', function () {
    $table = securityStub();
    $table->mount();
    $table->sortBy = '';

    $result = (new ReflectionMethod($table, 'safeSortBy'))->invoke($table);

    expect($result)->toBe('');
});

// ===========================================================================
// 1.2 – safeSortDir() – walidacja kierunku sortowania
// ===========================================================================

it('safeSortDir returns asc for invalid value', function () {
    $table = securityStub();
    $table->sortDir = 'INVALID; DROP TABLE';

    $result = (new ReflectionMethod($table, 'safeSortDir'))->invoke($table);

    expect($result)->toBe('asc');
});

it('safeSortDir returns asc when sortDir is asc', function () {
    $table = securityStub();
    $table->sortDir = 'asc';

    $result = (new ReflectionMethod($table, 'safeSortDir'))->invoke($table);

    expect($result)->toBe('asc');
});

it('safeSortDir returns desc when sortDir is desc', function () {
    $table = securityStub();
    $table->sortDir = 'desc';

    $result = (new ReflectionMethod($table, 'safeSortDir'))->invoke($table);

    expect($result)->toBe('desc');
});

it('safeSortDir returns asc for empty string', function () {
    $table = securityStub();
    $table->sortDir = '';

    $result = (new ReflectionMethod($table, 'safeSortDir'))->invoke($table);

    expect($result)->toBe('asc');
});

it('safeSortDir returns asc for ASC (uppercase not accepted)', function () {
    $table = securityStub();
    $table->sortDir = 'ASC';

    $result = (new ReflectionMethod($table, 'safeSortDir'))->invoke($table);

    expect($result)->toBe('asc');
});

// ===========================================================================
// 1.3 – authorizeAction() hook w metodach destruktywnych
// ===========================================================================

it('authorizeAction is a protected method returning void by default', function () {
    $table = securityStub();
    $method = new ReflectionMethod($table, 'authorizeAction');

    expect($method->isProtected())->toBeTrue();

    // Domyślna implementacja nie rzuca wyjątku
    $method->invoke($table, 'delete', null);
    $method->invoke($table, 'massDelete');
    $method->invoke($table, 'create');
    $method->invoke($table, 'update', new stdClass);

    expect(true)->toBeTrue();
});

it('deleteRow aborts when authorizeAction throws', function () {
    $mockRecord = new stdClass;
    $mockBuilder = Mockery::mock(Builder::class);
    $mockBuilder->shouldReceive('where')->andReturnSelf();
    $mockBuilder->shouldReceive('firstOrFail')->andReturn($mockRecord);

    $table = new class($mockBuilder) extends BaseTable
    {
        public function __construct(private readonly mixed $builder) {}

        protected function baseQuery(): Builder
        {
            return $this->builder;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        protected function authorizeAction(string $action, mixed $record = null): void
        {
            throw new AuthorizationException('Brak dostępu');
        }
    };

    (new ReflectionProperty($table, 'defaultActions'))->setValue($table, true);
    (new ReflectionProperty($table, 'defaultActionDelete'))->setValue($table, true);

    expect(fn () => $table->deleteRow('1'))
        ->toThrow(AuthorizationException::class, 'Brak dostępu');
});

it('massDelete aborts when authorizeAction throws', function () {
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

        protected function authorizeAction(string $action, mixed $record = null): void
        {
            throw new AuthorizationException('Brak dostępu');
        }
    };

    (new ReflectionProperty($table, 'selectable'))->setValue($table, true);
    $table->selected = ['1', '2'];

    expect(fn () => $table->massDelete())
        ->toThrow(AuthorizationException::class);
});

it('massEditUpdate aborts when authorizeAction throws', function () {
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

        public function creatingFields(): array
        {
            return [['key' => 'name', 'label' => 'Nazwa', 'type' => 'text']];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return [];
        }

        protected function authorizeAction(string $action, mixed $record = null): void
        {
            throw new AuthorizationException('Brak dostępu');
        }
    };

    (new ReflectionProperty($table, 'massEdit'))->setValue($table, true);
    $table->massEditData = ['name' => 'Jan'];
    $table->selected = ['1'];

    expect(fn () => $table->massEditUpdate())
        ->toThrow(AuthorizationException::class);
});

it('updateRecord aborts when authorizeAction throws', function () {
    $mockRecord = new stdClass;
    $mockBuilder = Mockery::mock(Builder::class);
    $mockBuilder->shouldReceive('where')->andReturnSelf();
    $mockBuilder->shouldReceive('firstOrFail')->andReturn($mockRecord);

    $table = new class($mockBuilder) extends BaseTable
    {
        public string $editingId = '1';

        public function __construct(private readonly mixed $builder) {}

        protected function baseQuery(): Builder
        {
            return $this->builder;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function creatingFields(): array
        {
            return [['key' => 'name', 'label' => 'Nazwa', 'type' => 'text']];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return [];
        }

        protected function authorizeAction(string $action, mixed $record = null): void
        {
            throw new AuthorizationException('Brak dostępu');
        }
    };

    (new ReflectionProperty($table, 'defaultActions'))->setValue($table, true);
    $table->editingData = ['name' => 'Test'];

    expect(fn () => $table->updateRecord())
        ->toThrow(AuthorizationException::class);
});

// ===========================================================================
// 1.4 – Automatyczne haszowanie pól password
// ===========================================================================

it('hashPasswordFields hashes values matching password pattern', function () {
    $table = securityStub();
    $method = new ReflectionMethod($table, 'hashPasswordFields');

    $data = ['name' => 'Jan', 'password' => 'secret123'];
    $result = $method->invoke($table, $data);

    expect($result['name'])->toBe('Jan');
    expect(Hash::check('secret123', $result['password']))->toBeTrue();
});

it('hashPasswordFields hashes fields matching *_password pattern', function () {
    $table = securityStub();
    $method = new ReflectionMethod($table, 'hashPasswordFields');

    $data = ['api_password' => 'mytoken'];
    $result = $method->invoke($table, $data);

    expect(Hash::check('mytoken', $result['api_password']))->toBeTrue();
});

it('hashPasswordFields does nothing when autoHashPasswords is false', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'autoHashPasswords'))->setValue($table, false);

    $method = new ReflectionMethod($table, 'hashPasswordFields');
    $data = ['password' => 'secret123'];
    $result = $method->invoke($table, $data);

    expect($result['password'])->toBe('secret123'); // niezmienione
});

it('hashPasswordFields skips empty password values', function () {
    $table = securityStub();
    $method = new ReflectionMethod($table, 'hashPasswordFields');

    $data = ['password' => ''];
    $result = $method->invoke($table, $data);

    expect($result['password'])->toBe(''); // puste zostawione bez zmian
});

// ===========================================================================
// 1.5 – $creatableFields whitelist
// ===========================================================================

it('creatableFields defaults to empty array', function () {
    $table = securityStub();
    $prop = new ReflectionProperty($table, 'creatableFields');

    expect($prop->getValue($table))->toBe([]);
});

it('editableFields defaults to empty array', function () {
    $table = securityStub();
    $prop = new ReflectionProperty($table, 'editableFields');

    expect($prop->getValue($table))->toBe([]);
});

// ===========================================================================
// 1.6 – Limit $selected
// ===========================================================================

it('toggleSelectRow does not add when maxSelected limit is reached', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 3);

    $table->selected = ['1', '2', '3'];
    $table->toggleSelectRow('4');

    expect($table->selected)->toHaveCount(3);
    expect($table->selected)->not->toContain('4');
});

it('toggleSelectRow still removes when at limit', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 3);

    $table->selected = ['1', '2', '3'];
    $table->toggleSelectRow('1'); // usunięcie działa nawet przy limicie

    expect($table->selected)->not->toContain('1');
    expect($table->selected)->toHaveCount(2);
});

it('selectRows does not exceed maxSelected limit', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 3);

    $table->selected = ['1'];
    $table->selectRows(['2', '3', '4', '5']); // limit 3, były 1, dodajemy max 2

    expect($table->selected)->toHaveCount(3);
});

it('selectRows adds nothing when already at maxSelected limit', function () {
    $table = securityStub();
    (new ReflectionProperty($table, 'maxSelected'))->setValue($table, 2);

    $table->selected = ['1', '2'];
    $table->selectRows(['3', '4']);

    expect($table->selected)->toHaveCount(2);
    expect($table->selected)->not->toContain('3');
});

it('maxSelected defaults to 10000', function () {
    $table = securityStub();
    $prop = new ReflectionProperty($table, 'maxSelected');

    expect($prop->getValue($table))->toBe(10_000);
});
