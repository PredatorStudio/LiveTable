<?php

use PredatorStudio\LiveTable\Cells\CheckboxCell;

it('renders checked for true', function () {
    $cell = new CheckboxCell();
    $cell->setColumnKey('is_active');
    $result = $cell->renderEditable((object)['id' => 1], true, '1');
    expect($result)->toContain('checked');
});

it('renders unchecked for false', function () {
    $cell = new CheckboxCell();
    $cell->setColumnKey('is_active');
    $result = $cell->renderEditable((object)['id' => 1], false, '1');
    // 'checked' as standalone HTML attribute must be absent;
    // $event.target.checked in the wire:change JS is acceptable
    expect($result)->not->toMatch('/\s+checked[\s\n\r>\/]/');
});

it('renders wire change call', function () {
    $cell = new CheckboxCell();
    $cell->setColumnKey('is_active');
    $result = $cell->renderEditable((object)['id' => 1], true, '1');
    expect($result)->toContain('updateCell');
    expect($result)->toContain('is_active');
});

it('calls update on model row', function () {
    $cell = new CheckboxCell();
    $cell->setColumnKey('is_active');

    $row = new class {
        public bool $is_active = false;
        public bool $saved = false;
        public function save(): void { $this->saved = true; }
    };

    $cell->update($row, true);

    expect($row->is_active)->toBeTrue();
    expect($row->saved)->toBeTrue();
});
