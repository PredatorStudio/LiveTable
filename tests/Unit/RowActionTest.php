<?php

use PredatorStudio\LiveTable\RowAction;

// ---------------------------------------------------------------------------
// make() + defaults
// ---------------------------------------------------------------------------

it('creates row action with label via make', function () {
    $action = RowAction::make('Edytuj');

    expect($action->label)->toBe('Edytuj')
        ->and($action->method)->toBe('')
        ->and($action->href)->toBe('')
        ->and($action->icon)->toBe('')
        ->and($action->confirm)->toBe('');
});

// ---------------------------------------------------------------------------
// Fluent setters
// ---------------------------------------------------------------------------

it('sets method via fluent method()', function () {
    $action = RowAction::make('Usuń')->method('deleteRow');

    expect($action->method)->toBe('deleteRow')
        ->and($action->label)->toBe('Usuń');
});

it('sets string href via fluent href()', function () {
    $action = RowAction::make('Edytuj')->href('/users/1/edit');

    expect($action->href)->toBe('/users/1/edit');
});

it('sets closure href via fluent href()', function () {
    $fn     = fn ($row) => '/users/' . $row->id . '/edit';
    $action = RowAction::make('Edytuj')->href($fn);

    expect($action->href)->toBe($fn);
});

it('sets icon via fluent icon()', function () {
    $action = RowAction::make('Edytuj')->icon('<svg/>');

    expect($action->icon)->toBe('<svg/>');
});

it('sets confirm via fluent confirm()', function () {
    $action = RowAction::make('Usuń')->confirm('Na pewno?');

    expect($action->confirm)->toBe('Na pewno?');
});

it('fluent setters return new instances', function () {
    $original = RowAction::make('Edytuj');

    expect($original->method('x'))->not->toBe($original)
        ->and($original->href('/x'))->not->toBe($original)
        ->and($original->icon('<svg/>'))->not->toBe($original)
        ->and($original->confirm('x'))->not->toBe($original);
});

it('fluent setters preserve other properties', function () {
    $action = RowAction::make('Edytuj')
        ->method('editRow')
        ->icon('<svg/>')
        ->confirm('Edytować?');

    $modified = $action->href('/x');

    expect($modified->method)->toBe('editRow')
        ->and($modified->icon)->toBe('<svg/>')
        ->and($modified->confirm)->toBe('Edytować?')
        ->and($modified->label)->toBe('Edytuj');
});

// ---------------------------------------------------------------------------
// resolveHref()
// ---------------------------------------------------------------------------

it('resolveHref returns string href as-is', function () {
    $action = RowAction::make('Edytuj')->href('/users/42');
    $row    = (object) ['id' => 42];

    expect($action->resolveHref($row))->toBe('/users/42');
});

it('resolveHref calls closure with row and returns string', function () {
    $action = RowAction::make('Edytuj')->href(fn ($row) => '/users/' . $row->id . '/edit');
    $row    = (object) ['id' => 7];

    expect($action->resolveHref($row))->toBe('/users/7/edit');
});

it('resolveHref returns empty string when no href set', function () {
    $action = RowAction::make('Usuń')->method('deleteRow');
    $row    = (object) ['id' => 1];

    expect($action->resolveHref($row))->toBe('');
});