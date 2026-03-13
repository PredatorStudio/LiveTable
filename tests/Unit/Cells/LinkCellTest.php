<?php

use PredatorStudio\LiveTable\Cells\LinkCell;

it('renders link with url from closure', function () {
    $cell = new LinkCell(urlResolver: fn ($row) => 'https://example.com/'.$row->id);
    $row = (object) ['id' => 5, 'name' => 'Jan'];
    $result = $cell->render($row, 'Jan');
    expect($result)->toContain('href="https://example.com/5"');
    expect($result)->toContain('Jan');
});

it('renders link with custom label closure', function () {
    $cell = new LinkCell(
        urlResolver: fn ($row) => 'https://example.com',
        labelResolver: fn ($row, $value) => 'Otwórz: '.$value,
    );
    $row = (object) ['name' => 'Jan'];
    $result = $cell->render($row, 'Jan');
    expect($result)->toContain('Otwórz: Jan');
});

it('renders empty for null value', function () {
    $cell = new LinkCell(urlResolver: fn ($row) => 'https://example.com');
    expect($cell->render((object) [], null))->toContain('—');
});

it('escapes label', function () {
    $cell = new LinkCell(urlResolver: fn ($row) => 'https://example.com');
    $result = $cell->render((object) [], '<b>XSS</b>');
    expect($result)->not->toContain('<b>');
});
