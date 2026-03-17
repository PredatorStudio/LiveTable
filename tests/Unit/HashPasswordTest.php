<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
// Stub model with password field
// ---------------------------------------------------------------------------

if (! class_exists('FakePasswordModel')) {
    class FakePasswordModel extends Model
    {
        protected $table = 'fake_password_models';

        protected $fillable = ['name', 'password', 'backup_password', 'email'];

        public static function create(array $attributes = []): static
        {
            return new static($attributes);
        }
    }
}

// ---------------------------------------------------------------------------
// Helper – calls private hashPasswordFields() via reflection
// ---------------------------------------------------------------------------

function callHashPasswordFields(BaseTable $table, array $data): array
{
    $method = new ReflectionMethod(BaseTable::class, 'hashPasswordFields');
    $method->setAccessible(true);

    return $method->invoke($table, $data);
}

function makePasswordTable(bool $autoHash = true): BaseTable
{
    return new class($autoHash) extends BaseTable
    {
        public function __construct(bool $hash)
        {
            $this->autoHashPasswords = $hash;
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
// hashPasswordFields() – isolation via reflection
// ---------------------------------------------------------------------------

it('hashes a field named password', function () {
    $table = makePasswordTable();
    $result = callHashPasswordFields($table, ['password' => 'secret123']);

    expect(Hash::check('secret123', $result['password']))->toBeTrue();
});

it('hashes a field matching *_password pattern', function () {
    $table = makePasswordTable();
    $result = callHashPasswordFields($table, ['backup_password' => 'mypass']);

    expect(Hash::check('mypass', $result['backup_password']))->toBeTrue();
});

it('does not hash empty password value', function () {
    $table = makePasswordTable();
    $result = callHashPasswordFields($table, ['password' => '']);

    expect($result['password'])->toBe('');
});

it('does not hash null password value', function () {
    $table = makePasswordTable();
    $result = callHashPasswordFields($table, ['password' => null]);

    expect($result['password'])->toBeNull();
});

it('returns data unchanged when autoHashPasswords is false', function () {
    $table = makePasswordTable(autoHash: false);
    $result = callHashPasswordFields($table, ['password' => 'plaintext']);

    expect($result['password'])->toBe('plaintext');
});

// ---------------------------------------------------------------------------
// Integration – password hashing flows through createRecord()
// ---------------------------------------------------------------------------

it('createRecord stores a hashed password, not plaintext', function () {
    $createdRecord = null;

    $table = new class extends BaseTable
    {
        public ?object $createdRecord = null;

        public function __construct()
        {
            $this->model = FakePasswordModel::class;
            $this->defaultCreating = true;
            $this->creatingData = ['name' => 'Jan', 'password' => 'secret', 'backup_password' => '', 'email' => 'j@b.com'];
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return $this->creatingData;
        }

        protected function afterCreate(mixed $record): void
        {
            $this->createdRecord = $record;
        }
    };

    $table->createRecord();

    expect($table->createdRecord)->not->toBeNull();
    expect($table->createdRecord->password)->not->toBe('secret');
    expect(Hash::check('secret', $table->createdRecord->password))->toBeTrue();
});

it('createRecord does not hash password when autoHashPasswords is false', function () {
    $table = new class extends BaseTable
    {
        public ?object $createdRecord = null;

        public function __construct()
        {
            $this->model = FakePasswordModel::class;
            $this->defaultCreating = true;
            $this->autoHashPasswords = false;
            $this->creatingData = ['name' => 'Jan', 'password' => 'plaintext', 'backup_password' => '', 'email' => 'j@b.com'];
        }

        protected function baseQuery(): Builder
        {
            return Mockery::mock(Builder::class);
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            return $this->creatingData;
        }

        protected function afterCreate(mixed $record): void
        {
            $this->createdRecord = $record;
        }
    };

    $table->createRecord();

    expect($table->createdRecord->password)->toBe('plaintext');
});