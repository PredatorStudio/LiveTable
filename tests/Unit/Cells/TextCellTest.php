<?php

use PredatorStudio\LiveTable\Cells\TextCell;

it('renders string value', function () {
    $cell = new TextCell;
    $row = (object) ['name' => 'Jan'];
    expect($cell->render($row, 'Jan'))->toBe('Jan');
});

it('renders empty dash for null', function () {
    $cell = new TextCell;
    $row = (object) [];
    expect($cell->render($row, null))->toContain('—');
});

it('renders empty dash for empty string', function () {
    $cell = new TextCell;
    $row = (object) [];
    expect($cell->render($row, ''))->toContain('—');
});

it('escapes html', function () {
    $cell = new TextCell;
    $row = (object) [];
    $result = $cell->render($row, '<script>alert(1)</script>');
    expect($result)->not->toContain('<script>');
    expect($result)->toContain('&lt;script&gt;');
});
