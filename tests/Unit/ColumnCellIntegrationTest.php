<?php

use Orchestra\Testbench\TestCase;

uses(TestCase::class);
use PredatorStudio\LiveTable\Cells\Cell;
use PredatorStudio\LiveTable\Cells\SelectCell;
use PredatorStudio\LiveTable\Cells\TextCell;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Enums\DateFormat;
use PredatorStudio\LiveTable\Enums\DateTimeFormat;
use PredatorStudio\LiveTable\Enums\MoneyFormat;
use PredatorStudio\LiveTable\Enums\TimeFormat;

it('uses text cell by default via make', function () {
    $col = Column::make('name', 'Nazwa');
    $row = (object) ['name' => 'Jan'];
    expect($col->renderCell($row))->toBe('Jan');
});

it('creates text column via factory', function () {
    $col = Column::text('name', 'Nazwa');
    expect($col->renderCell((object) ['name' => 'Ala']))->toBe('Ala');
});

it('creates number column via factory', function () {
    $col = Column::number('count', 'Ilość');
    expect($col->renderCell((object) ['count' => 5]))->toBe('5');
});

it('creates date column via factory', function () {
    $col = Column::date('created_at', 'Data', DateFormat::YMD);
    expect($col->renderCell((object) ['created_at' => '2026-03-08']))->toBe('2026-03-08');
});

it('creates datetime column via factory', function () {
    $col = Column::dateTime('created_at', 'Data', DateTimeFormat::YMD_HM);
    expect($col->renderCell((object) ['created_at' => '2026-03-08 14:30:00']))->toBe('2026-03-08 14:30');
});

it('creates time column via factory', function () {
    $col = Column::time('hour', 'Czas', TimeFormat::HI);
    expect($col->renderCell((object) ['hour' => '14:30:00']))->toBe('14:30');
});

it('creates money column via factory', function () {
    $col = Column::money('price', 'Cena', MoneyFormat::SPACE_COMMA, 'PLN');
    expect($col->renderCell((object) ['price' => 10000]))->toBe('10 000,00 PLN');
});

it('creates link column via factory', function () {
    $col = Column::link('url', 'Link', fn ($row) => 'https://example.com/'.$row->id);
    $result = $col->renderCell((object) ['id' => 3, 'url' => 'test']);
    expect($result)->toContain('href="https://example.com/3"');
});

it('creates badge column via factory', function () {
    $col = Column::badge('status', 'Status', ['active' => 'success']);
    $result = $col->renderCell((object) ['status' => 'active']);
    expect($result)->toContain('bg-success');
});

it('creates select column via factory with SelectCell', function () {
    $col = Column::select('status', 'Status', SelectCell::fromArray(['a' => 'A', 'b' => 'B']));
    $result = $col->renderCell((object) ['status' => 'a'], 'id');
    expect($result)->toContain('<select');
    expect($result)->toContain('value="a"');
});

it('creates checkbox column via factory', function () {
    $col = Column::checkbox('active', 'Aktywny');
    $result = $col->renderCell((object) ['active' => true, 'id' => 1], 'id');
    expect($result)->toContain('type="checkbox"');
    expect($result)->toContain('checked');
});

it('creates custom column via factory', function () {
    $customCell = new class extends Cell
    {
        public function render(mixed $row, mixed $value): string
        {
            return 'CUSTOM';
        }
    };
    $col = Column::custom('field', 'Label', $customCell);
    expect($col->renderCell((object) ['field' => 'x']))->toBe('CUSTOM');
});

it('prefers format closure over cell type', function () {
    $col = Column::date('created_at', 'Data', DateFormat::DMY)
        ->format(fn ($row, $v) => 'OVERRIDE');
    expect($col->renderCell((object) ['created_at' => '2026-01-01']))->toBe('OVERRIDE');
});

it('allows cell override via fluent method', function () {
    $col = Column::make('name', 'Nazwa')->cell(new TextCell);
    expect($col->renderCell((object) ['name' => 'Test']))->toBe('Test');
});

it('injects column key into editable cell on assignment', function () {
    $cell = SelectCell::fromArray(['a' => 'A']);
    Column::make('status', 'Status')->cell($cell);

    // columnKey is used in wire:change HTML – verify it was set
    $html = $cell->renderEditable((object) [], 'a', '1');
    expect($html)->toContain('status');
});

it('column key is set via static select factory', function () {
    $cell = SelectCell::fromArray(['x' => 'X']);
    Column::select('my_field', 'Pole', $cell);

    $html = $cell->renderEditable((object) [], 'x', '99');
    expect($html)->toContain('my_field');
});
