<?php

use PredatorStudio\LiveTable\Action;

it('creates action with label via make', function () {
    $action = Action::make('Dodaj');

    expect($action->label)->toBe('Dodaj');
    expect($action->method)->toBe('');
    expect($action->href)->toBe('');
    expect($action->icon)->toBe('');
});

it('sets method via fluent method()', function () {
    $action = Action::make('Zapisz')->method('save');

    expect($action->method)->toBe('save');
    expect($action->label)->toBe('Zapisz');
});

it('sets href via fluent href()', function () {
    $action = Action::make('Idź')->href('/dashboard');

    expect($action->href)->toBe('/dashboard');
    expect($action->label)->toBe('Idź');
});

it('sets icon via fluent icon()', function () {
    $action = Action::make('Ikona')->icon('<svg/>');

    expect($action->icon)->toBe('<svg/>');
});

