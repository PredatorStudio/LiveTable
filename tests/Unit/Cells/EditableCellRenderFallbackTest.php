<?php

use PredatorStudio\LiveTable\Cells\CheckboxCell;
use PredatorStudio\LiveTable\Cells\SelectCell;

// Tests for EditableCell::render() – the fallback that calls renderEditable() with empty rowId.
// This path is hit when a Cell is used without providing a primaryKey (e.g. in non-Livewire contexts).

it('checkbox render() delegates to renderEditable with empty rowId', function () {
    $cell = new CheckboxCell;
    $cell->setColumnKey('active');

    $result = $cell->render((object) [], true);

    expect($result)->toContain('type="checkbox"');
    expect($result)->toContain('checked');
});

it('select render() delegates to renderEditable with empty rowId', function () {
    $cell = SelectCell::fromArray(['a' => 'Opcja A']);
    $cell->setColumnKey('status');

    $result = $cell->render((object) [], 'a');

    expect($result)->toContain('<select');
    expect($result)->toContain('Opcja A');
});
