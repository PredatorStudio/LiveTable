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

it('creates number filter', function () {
    $filter = Filter::number('quantity', 'Ilość');

    expect($filter->key)->toBe('quantity');
    expect($filter->label)->toBe('Ilość');
    expect($filter->type)->toBe(FilterType::NUMBER);
    expect($filter->options)->toBe([]);
});

it('creates number range filter', function () {
    $filter = Filter::numberRange('price', 'Cena');

    expect($filter->key)->toBe('price');
    expect($filter->label)->toBe('Cena');
    expect($filter->type)->toBe(FilterType::NUMBER_RANGE);
    expect($filter->options)->toBe([]);
});

it('creates date range filter', function () {
    $filter = Filter::dateRange('created_at', 'Data');

    expect($filter->key)->toBe('created_at');
    expect($filter->label)->toBe('Data');
    expect($filter->type)->toBe(FilterType::DATE_RANGE);
    expect($filter->options)->toBe([]);
});

it('creates datetime filter', function () {
    $filter = Filter::datetime('published_at', 'Opublikowano');

    expect($filter->key)->toBe('published_at');
    expect($filter->label)->toBe('Opublikowano');
    expect($filter->type)->toBe(FilterType::DATETIME);
    expect($filter->options)->toBe([]);
});

it('creates datetime range filter', function () {
    $filter = Filter::datetimeRange('published_at', 'Opublikowano');

    expect($filter->key)->toBe('published_at');
    expect($filter->label)->toBe('Opublikowano');
    expect($filter->type)->toBe(FilterType::DATETIME_RANGE);
    expect($filter->options)->toBe([]);
});

it('creates time filter', function () {
    $filter = Filter::time('start_time', 'Godzina');

    expect($filter->key)->toBe('start_time');
    expect($filter->label)->toBe('Godzina');
    expect($filter->type)->toBe(FilterType::TIME);
    expect($filter->options)->toBe([]);
});

it('creates boolean filter', function () {
    $filter = Filter::boolean('is_active', 'Aktywny');

    expect($filter->key)->toBe('is_active');
    expect($filter->label)->toBe('Aktywny');
    expect($filter->type)->toBe(FilterType::BOOLEAN);
    expect($filter->options)->toBe([]);
});

it('creates money filter', function () {
    $filter = Filter::money('price', 'Cena');

    expect($filter->key)->toBe('price');
    expect($filter->label)->toBe('Cena');
    expect($filter->type)->toBe(FilterType::MONEY);
    expect($filter->options)->toBe([]);
});

// ---------------------------------------------------------------------------
// normalizeMoney()
// ---------------------------------------------------------------------------

it('normalizes integer amount', function () {
    expect(Filter::normalizeMoney('1000'))->toBe(1000.0);
});

it('normalizes amount with dot as decimal separator', function () {
    expect(Filter::normalizeMoney('1234.56'))->toBe(1234.56);
});

it('normalizes amount with comma as decimal separator', function () {
    expect(Filter::normalizeMoney('1234,56'))->toBe(1234.56);
});

it('normalizes European format with dot thousands and comma decimal', function () {
    expect(Filter::normalizeMoney('1.234,56'))->toBe(1234.56);
});

it('normalizes American format with comma thousands and dot decimal', function () {
    expect(Filter::normalizeMoney('1,234.56'))->toBe(1234.56);
});

it('normalizes amount with currency symbol', function () {
    expect(Filter::normalizeMoney('1 000,00 zł'))->toBe(1000.0);
    expect(Filter::normalizeMoney('$1,234.56'))->toBe(1234.56);
});

it('normalizes amount with spaces as thousands separator', function () {
    expect(Filter::normalizeMoney('1 000 000'))->toBe(1000000.0);
});

it('returns null for non-numeric string', function () {
    expect(Filter::normalizeMoney('abc'))->toBeNull();
    expect(Filter::normalizeMoney(''))->toBeNull();
});
