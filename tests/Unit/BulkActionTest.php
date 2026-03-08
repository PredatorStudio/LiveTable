<?php

use PredatorStudio\LiveTable\BulkAction;

it('creates bulk action with method and label via make', function () {
    $action = BulkAction::make('deleteSelected', 'Usuń zaznaczone');

    expect($action->method)->toBe('deleteSelected');
    expect($action->label)->toBe('Usuń zaznaczone');
    expect($action->icon)->toBe('');
});

it('sets icon via fluent icon()', function () {
    $action = BulkAction::make('delete', 'Usuń')->icon('<svg/>');

    expect($action->icon)->toBe('<svg/>');
    expect($action->method)->toBe('delete');
    expect($action->label)->toBe('Usuń');
});

it('icon() returns new instance', function () {
    $original = BulkAction::make('delete', 'Usuń');
    $modified  = $original->icon('<svg/>');

    expect($modified)->not->toBe($original);
});
