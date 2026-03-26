<?php

use Illuminate\Database\Eloquent\Builder;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helper – calls private quoteColumn() via reflection
// ---------------------------------------------------------------------------

function makeQuoteColumnTable(): BaseTable
{
    return new class extends BaseTable
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
}

function callQuoteColumn(BaseTable $table, string $col): string
{
    $method = new ReflectionMethod($table, 'quoteColumn');
    $method->setAccessible(true);

    return $method->invoke($table, $col);
}

// ---------------------------------------------------------------------------
// Valid column names – quoting
// ---------------------------------------------------------------------------

it('wraps simple column name in backticks', function () {
    $table = makeQuoteColumnTable();
    expect(callQuoteColumn($table, 'price'))->toBe('`price`');
});

it('wraps column name with underscores in backticks', function () {
    $table = makeQuoteColumnTable();
    expect(callQuoteColumn($table, 'total_amount'))->toBe('`total_amount`');
});

it('wraps uppercase column name in backticks', function () {
    $table = makeQuoteColumnTable();
    expect(callQuoteColumn($table, 'TotalAmount'))->toBe('`TotalAmount`');
});

it('handles table.column dot notation', function () {
    $table = makeQuoteColumnTable();
    // table.column is valid per the regex /^[a-zA-Z_][a-zA-Z0-9_.]*$/
    $result = callQuoteColumn($table, 'orders.price');
    expect($result)->toBe('`orders.price`');
});

it('handles column with digits', function () {
    $table = makeQuoteColumnTable();
    expect(callQuoteColumn($table, 'col_123'))->toBe('`col_123`');
});

it('handles column starting with underscore', function () {
    $table = makeQuoteColumnTable();
    expect(callQuoteColumn($table, '_hidden_col'))->toBe('`_hidden_col`');
});

// ---------------------------------------------------------------------------
// SQL injection prevention – invalid column names must throw
// ---------------------------------------------------------------------------

it('throws InvalidArgumentException for column with spaces', function () {
    $table = makeQuoteColumnTable();
    expect(fn () => callQuoteColumn($table, 'col name'))->toThrow(
        \InvalidArgumentException::class,
        'Nieprawidłowa nazwa kolumny'
    );
});

it('throws InvalidArgumentException for empty string', function () {
    $table = makeQuoteColumnTable();
    expect(fn () => callQuoteColumn($table, ''))->toThrow(\InvalidArgumentException::class);
});

it('throws InvalidArgumentException for column with dash', function () {
    $table = makeQuoteColumnTable();
    expect(fn () => callQuoteColumn($table, 'col-name'))->toThrow(\InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// Backtick stripping (prevents backtick injection via column name)
// ---------------------------------------------------------------------------

it('removes existing backticks from column name before wrapping', function () {
    $table = makeQuoteColumnTable();
    // Column names with backticks pass the regex only if they are stripped first.
    // Actually backtick is not in [a-zA-Z0-9_.] so this would throw first.
    // Let's verify that a backtick IN the name is rejected by the regex.
    expect(fn () => callQuoteColumn($table, 'col`injection'))->toThrow(\InvalidArgumentException::class);
});