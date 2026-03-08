<?php

use PredatorStudio\LiveTable\Cells\SelectCell;

it('builds options from 2d array', function () {
    $cell = SelectCell::fromArray(['active' => 'Aktywny', 'banned' => 'Zbanowany']);
    $cell->setColumnKey('status');
    $result = $cell->renderEditable((object)[], 'active', '1');
    expect($result)->toContain('<option value="active"');
    expect($result)->toContain('Aktywny');
});

it('builds options from 1d array', function () {
    $cell = SelectCell::fromArray(['raz', 'dwa', 'trzy']);
    $cell->setColumnKey('status');
    $result = $cell->renderEditable((object)[], 'raz', '1');
    expect($result)->toContain('raz');
    expect($result)->toContain('dwa');
});

it('builds options from backed enum', function () {
    $cell = SelectCell::fromEnum(TestStatus::class);
    $cell->setColumnKey('status');
    $result = $cell->renderEditable((object)[], 'active', '1');
    expect($result)->toContain('active');
});

it('renders select with current value selected', function () {
    $cell = SelectCell::fromArray(['active' => 'Aktywny', 'banned' => 'Zbanowany']);
    $cell->setColumnKey('status');
    $result = $cell->renderEditable((object)[], 'banned', '1');
    expect($result)->toContain('value="banned" selected');
});

it('renders wire change call', function () {
    $cell = SelectCell::fromArray(['a' => 'A']);
    $cell->setColumnKey('status');
    $result = $cell->renderEditable((object)[], 'a', '5');
    expect($result)->toContain('updateCell');
});

it('calls update on model', function () {
    $cell = SelectCell::fromArray(['active' => 'Aktywny']);
    $cell->setColumnKey('status');

    $row = new class {
        public string $status = 'banned';
        public bool $saved = false;
        public function save(): void { $this->saved = true; }
    };

    $cell->update($row, 'active');

    expect($row->status)->toBe('active');
    expect($row->saved)->toBeTrue();
});

it('builds options from collection via fromQuery', function () {
    $collection = collect([
        (object) ['id' => 1, 'name' => 'Jan'],
        (object) ['id' => 2, 'name' => 'Anna'],
    ]);

    $cell = SelectCell::fromQuery($collection);
    $cell->setColumnKey('user_id');

    $result = $cell->renderEditable((object) [], '1', '5');

    expect($result)->toContain('value="1"');
    expect($result)->toContain('Jan');
    expect($result)->toContain('value="2"');
    expect($result)->toContain('Anna');
});

it('builds options from collection with custom field names via fromQuery', function () {
    $collection = collect([
        (object) ['code' => 'pl', 'label' => 'Polski'],
        (object) ['code' => 'en', 'label' => 'English'],
    ]);

    $cell = SelectCell::fromQuery($collection, valueField: 'code', labelField: 'label');
    $cell->setColumnKey('lang');

    $result = $cell->renderEditable((object) [], 'pl', '1');

    expect($result)->toContain('value="pl"');
    expect($result)->toContain('Polski');
});

enum TestStatus: string
{
    case Active  = 'active';
    case Banned  = 'banned';
}
