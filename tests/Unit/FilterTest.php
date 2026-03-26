<?php

use PredatorStudio\LiveTable\Enums\FilterType;
use PredatorStudio\LiveTable\Filter;

it('creates text filter', function () {
    $filter = Filter::text('name', 'Nazwa');

    expect($filter->key)->toBe('name');
    expect($filter->label)->toBe('Nazwa');
    expect($filter->type)->toBe(FilterType::TEXT);
    expect($filter->options)->toBe([]);
});

it('creates select filter with options', function () {
    $options = ['active' => 'Aktywny', 'banned' => 'Zbanowany'];
    $filter = Filter::select('status', 'Status', $options);

    expect($filter->key)->toBe('status');
    expect($filter->label)->toBe('Status');
    expect($filter->type)->toBe(FilterType::SELECT);
    expect($filter->options)->toBe($options);
});

it('creates date filter', function () {
    $filter = Filter::date('created_at', 'Data');

    expect($filter->key)->toBe('created_at');
    expect($filter->label)->toBe('Data');
    expect($filter->type)->toBe(FilterType::DATE);
    expect($filter->options)->toBe([]);
});

