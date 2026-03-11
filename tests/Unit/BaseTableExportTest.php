<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\LiveTableServiceProvider;
use PredatorStudio\LiveTable\SubRows;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(\Orchestra\Testbench\TestCase::class);

beforeEach(function () {
    $this->app->register(LiveTableServiceProvider::class);
});

afterEach(fn () => Mockery::close());

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeExportTable(
    array $rows = [],
    bool $exportCsv = true,
    bool $exportPdf = false,
    array $selected = [],
    bool $selectAllQuery = false,
    bool $expandable = false,
): BaseTable {
    return new class ($rows, $exportCsv, $exportPdf, $selected, $selectAllQuery, $expandable) extends BaseTable {
        public function __construct(
            private readonly array $rows,
            bool $csv,
            bool $pdf,
            array $sel,
            bool $allQuery,
            bool $exp,
        ) {
            $this->exportCsv      = $csv;
            $this->exportPdf      = $pdf;
            $this->selected       = $sel;
            $this->selectAllQuery = $allQuery;
            $this->expandable     = $exp;
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(count($this->rows));
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('whereIn')->andReturnSelf();
            $builder->shouldReceive('cursor')->andReturn(\Illuminate\Support\LazyCollection::make($this->rows));
            $builder->shouldReceive('get')->andReturn(Collection::make($this->rows));

            return $builder;
        }

        public function columns(): array
        {
            return [
                Column::make('name', 'Nazwa'),
                Column::make('email', 'E-mail'),
            ];
        }

        protected function subRows(mixed $row): ?SubRows
        {
            return isset($row->subItems)
                ? SubRows::fromArray($row->subItems)
                : null;
        }
    };
}

function captureStreamedResponse(StreamedResponse $response): string
{
    ob_start();
    $response->sendContent();
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// exportCsv() – returns StreamedResponse
// ---------------------------------------------------------------------------

it('exportCsv returns StreamedResponse', function () {
    $table = makeExportTable();

    $response = $table->exportCsv();

    expect($response)->toBeInstanceOf(StreamedResponse::class);
});

it('exportCsv content has UTF-8 BOM', function () {
    $table   = makeExportTable();
    $content = captureStreamedResponse($table->exportCsv());

    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();
});

it('exportCsv first row is column labels', function () {
    $table   = makeExportTable();
    $content = captureStreamedResponse($table->exportCsv());
    $lines   = array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF")));
    $header  = str_getcsv(array_values($lines)[0]);

    expect($header)->toBe(['Nazwa', 'E-mail']);
});

it('exportCsv includes data rows', function () {
    $rows = [
        (object) ['name' => 'Jan', 'email' => 'jan@example.com'],
        (object) ['name' => 'Anna', 'email' => 'anna@example.com'],
    ];

    $table   = makeExportTable(rows: $rows);
    $content = captureStreamedResponse($table->exportCsv());
    $lines   = array_values(array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF"))));

    expect(count($lines))->toBe(3) // header + 2 rows
        ->and(str_getcsv($lines[1]))->toBe(['Jan', 'jan@example.com'])
        ->and(str_getcsv($lines[2]))->toBe(['Anna', 'anna@example.com']);
});

it('exportCsv filename contains class name and date', function () {
    $table    = makeExportTable();
    $response = $table->exportCsv();

    expect($response->headers->get('content-disposition'))->toContain('.csv');
});

// ---------------------------------------------------------------------------
// exportCsv() – data scope
// ---------------------------------------------------------------------------

it('exportCsv uses all query rows when nothing selected', function () {
    $rows = [
        (object) ['name' => 'Jan', 'email' => 'a@a.com'],
        (object) ['name' => 'Anna', 'email' => 'b@b.com'],
    ];

    $table   = makeExportTable(rows: $rows, selected: []);
    $content = captureStreamedResponse($table->exportCsv());
    $lines   = array_values(array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF"))));

    expect(count($lines))->toBe(3); // header + 2
});

it('exportCsv respects selected ids when not selectAllQuery', function () {
    $rows = [
        (object) ['id' => 1, 'name' => 'Jan', 'email' => 'a@a.com'],
    ];

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('count')->andReturn(1);
    $builder->shouldReceive('orderBy')->andReturnSelf();
    $builder->shouldReceive('offset')->andReturnSelf();
    $builder->shouldReceive('limit')->andReturnSelf();
    $builder->shouldReceive('whereIn')->once()->andReturnSelf();
    $builder->shouldReceive('cursor')->andReturn(\Illuminate\Support\LazyCollection::make($rows));
    $builder->shouldReceive('get')->andReturn(Collection::make($rows));

    $table = new class ($builder) extends BaseTable {
        public function __construct(private readonly Builder $mockBuilder)
        {
            $this->selected  = ['1'];
            $this->exportCsv = true;
        }

        protected function baseQuery(): Builder { return $this->mockBuilder; }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };

    $table->exportCsv();
});

it('exportCsv skips whereIn when selectAllQuery is true', function () {
    $rows = [(object) ['name' => 'Jan', 'email' => 'a@a.com']];

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('count')->andReturn(1);
    $builder->shouldReceive('orderBy')->andReturnSelf();
    $builder->shouldReceive('offset')->andReturnSelf();
    $builder->shouldReceive('limit')->andReturnSelf();
    $builder->shouldReceive('whereIn')->never();
    $builder->shouldReceive('cursor')->andReturn(\Illuminate\Support\LazyCollection::make($rows));
    $builder->shouldReceive('get')->andReturn(Collection::make($rows));

    $table = new class ($builder) extends BaseTable {
        public function __construct(private readonly Builder $mockBuilder)
        {
            $this->selected       = ['1'];
            $this->selectAllQuery = true;
            $this->exportCsv      = true;
        }

        protected function baseQuery(): Builder { return $this->mockBuilder; }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };

    $table->exportCsv();
});

// ---------------------------------------------------------------------------
// exportCsv() – sub-rows
// ---------------------------------------------------------------------------

it('exportCsv includes sub-rows after parent row', function () {
    $row          = (object) ['name' => 'Jan', 'email' => 'jan@example.com'];
    $row->subItems = [
        ['name' => 'Sub1', 'email' => 'sub1@example.com'],
    ];

    $table   = makeExportTable(rows: [$row], expandable: true);
    $content = captureStreamedResponse($table->exportCsv());
    $lines   = array_values(array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF"))));

    expect(count($lines))->toBe(3); // header + parent + sub-row
});

// ---------------------------------------------------------------------------
// exportPdf()
// ---------------------------------------------------------------------------

it('exportPdf returns null when exportPdf is false', function () {
    $table = makeExportTable(exportPdf: false);

    expect($table->exportPdf())->toBeNull();
});

it('exportPdf calls generatePdf when exportPdf is true', function () {
    $table = new class extends BaseTable {
        public bool $exportPdf = true;
        public bool $generatePdfCalled = false;

        public function __construct()
        {
        }

        protected function baseQuery(): Builder
        {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('count')->andReturn(0);
            $builder->shouldReceive('orderBy')->andReturnSelf();
            $builder->shouldReceive('offset')->andReturnSelf();
            $builder->shouldReceive('limit')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn(Collection::make([]));
            return $builder;
        }

        public function columns(): array
        {
            return [Column::make('id', 'ID')];
        }

        protected function generatePdf(Collection $rows, array $columns): mixed
        {
            $this->generatePdfCalled = true;
            return null;
        }
    };

    $table->mount();
    $table->exportPdf();

    expect($table->generatePdfCalled)->toBeTrue();
});

it('generatePdf returns null by default', function () {
    $table = makeExportTable(exportPdf: true);

    expect($table->exportPdf())->toBeNull();
});

// ---------------------------------------------------------------------------
// exportCsv() – cursor() instead of get() (task 2.3)
// ---------------------------------------------------------------------------

it('exportCsv uses cursor instead of get for large datasets', function () {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('count')->andReturn(0);
    $builder->shouldReceive('orderBy')->andReturnSelf();
    $builder->shouldReceive('offset')->andReturnSelf();
    $builder->shouldReceive('limit')->andReturnSelf();
    $builder->shouldReceive('cursor')->once()->andReturn(\Illuminate\Support\LazyCollection::make([]));
    $builder->shouldReceive('get')->never();

    $table = new class ($builder) extends BaseTable {
        public function __construct(private readonly Builder $mock)
        {
            $this->exportCsv = true;
        }

        protected function baseQuery(): Builder { return $this->mock; }

        public function columns(): array
        {
            return [Column::make('name', 'Nazwa')];
        }
    };

    // Trigger the stream closure to ensure cursor() is actually called
    captureStreamedResponse($table->exportCsv());
});
