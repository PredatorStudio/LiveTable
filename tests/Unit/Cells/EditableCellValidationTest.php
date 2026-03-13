<?php

use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;
use PredatorStudio\LiveTable\Cells\CheckboxCell;
use PredatorStudio\LiveTable\Cells\EditableCell;
use PredatorStudio\LiveTable\Cells\SelectCell;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// EditableCell::validation() + validate()
// ---------------------------------------------------------------------------

it('skips validation when no rules are set', function () {
    $cell = SelectCell::fromArray(['a' => 'A']);
    $cell->setColumnKey('status');

    // Should not throw
    $cell->validate('anything');

    expect(true)->toBeTrue();
});

it('passes validation when value matches rules', function () {
    $cell = SelectCell::fromArray(['a' => 'A'])
        ->validation(['string', 'max:10']);
    $cell->setColumnKey('status');

    $cell->validate('valid');

    expect(true)->toBeTrue();
});

it('throws ValidationException when value breaks rules', function () {
    $cell = SelectCell::fromArray(['a' => 'A'])
        ->validation(['integer']);
    $cell->setColumnKey('status');

    $cell->validate('not-an-integer');
})->throws(ValidationException::class);

it('throws ValidationException for required rule on empty value', function () {
    $cell = (new CheckboxCell)->validation(['accepted']);
    $cell->setColumnKey('active');

    $cell->validate(false);
})->throws(ValidationException::class);

it('validation returns same instance for fluent chaining', function () {
    $cell = SelectCell::fromArray(['a' => 'A']);
    $result = $cell->validation(['string']);

    expect($result)->toBe($cell);
});
