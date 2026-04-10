<?php

use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Workbench\App\Livewire\DemoProductsTable;
use Workbench\App\Models\DemoProduct;

uses(FeatureTestCase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function product(array $attrs = []): DemoProduct
{
    return DemoProduct::create(array_merge([
        'name'            => 'Produkt',
        'category'        => 'general',
        'price'           => '100.00',
        'quantity'        => 10,
        'is_active'       => true,
        'manufactured_at' => '2024-01-15',
    ], $attrs));
}

// ---------------------------------------------------------------------------
// TEXT filter
// ---------------------------------------------------------------------------

it('auto-applies text filter with LIKE', function () {
    product(['name' => 'Kawa Arabica']);
    product(['name' => 'Herbata Zielona']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['name' => 'Kawa'])
        ->call('applyActiveFilters')
        ->assertSee('Kawa Arabica')
        ->assertDontSee('Herbata Zielona');
});

// ---------------------------------------------------------------------------
// SELECT filter
// ---------------------------------------------------------------------------

it('auto-applies select filter with exact match', function () {
    product(['name' => 'Laptop',  'category' => 'electronics']);
    product(['name' => 'Chleb',   'category' => 'food']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['category' => 'electronics'])
        ->call('applyActiveFilters')
        ->assertSee('Laptop')
        ->assertDontSee('Chleb');
});

it('auto-applies select filter shows all when value empty', function () {
    product(['name' => 'Laptop',  'category' => 'electronics']);
    product(['name' => 'Chleb',   'category' => 'food']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['category' => ''])
        ->call('applyActiveFilters')
        ->assertSee('Laptop')
        ->assertSee('Chleb');
});

// ---------------------------------------------------------------------------
// NUMBER RANGE filter
// ---------------------------------------------------------------------------

it('auto-applies number range filter from only', function () {
    product(['name' => 'Mała ilość',  'quantity' => 5]);
    product(['name' => 'Duża ilość',  'quantity' => 50]);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['quantity' => ['from' => '20', 'to' => '']])
        ->call('applyActiveFilters')
        ->assertDontSee('Mała ilość')
        ->assertSee('Duża ilość');
});

it('auto-applies number range filter to only', function () {
    product(['name' => 'Mała ilość',  'quantity' => 5]);
    product(['name' => 'Duża ilość',  'quantity' => 50]);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['quantity' => ['from' => '', 'to' => '10']])
        ->call('applyActiveFilters')
        ->assertSee('Mała ilość')
        ->assertDontSee('Duża ilość');
});

it('auto-applies number range filter from and to', function () {
    product(['name' => 'Za mało',  'quantity' => 5]);
    product(['name' => 'W zakresie', 'quantity' => 30]);
    product(['name' => 'Za dużo', 'quantity' => 100]);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['quantity' => ['from' => '20', 'to' => '50']])
        ->call('applyActiveFilters')
        ->assertDontSee('Za mało')
        ->assertSee('W zakresie')
        ->assertDontSee('Za dużo');
});

// ---------------------------------------------------------------------------
// DATE RANGE filter
// ---------------------------------------------------------------------------

it('auto-applies date range filter', function () {
    product(['name' => 'Stary',   'manufactured_at' => '2023-01-01']);
    product(['name' => 'Nowy',    'manufactured_at' => '2024-06-01']);
    product(['name' => 'Najnowszy', 'manufactured_at' => '2025-01-01']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['manufactured_at' => ['from' => '2024-01-01', 'to' => '2024-12-31']])
        ->call('applyActiveFilters')
        ->assertDontSee('Stary')
        ->assertSee('Nowy')
        ->assertDontSee('Najnowszy');
});

// ---------------------------------------------------------------------------
// BOOLEAN filter
// ---------------------------------------------------------------------------

it('auto-applies boolean filter for active', function () {
    product(['name' => 'Produkt włączony',  'is_active' => true]);
    product(['name' => 'Produkt wyłączony', 'is_active' => false]);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['is_active' => '1'])
        ->call('applyActiveFilters')
        ->assertSee('Produkt włączony')
        ->assertDontSee('Produkt wyłączony');
});

it('auto-applies boolean filter for inactive', function () {
    product(['name' => 'Produkt włączony',  'is_active' => true]);
    product(['name' => 'Produkt wyłączony', 'is_active' => false]);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['is_active' => '0'])
        ->call('applyActiveFilters')
        ->assertDontSee('Produkt włączony')
        ->assertSee('Produkt wyłączony');
});

// ---------------------------------------------------------------------------
// MONEY filter
// ---------------------------------------------------------------------------

it('auto-applies money filter with plain numbers', function () {
    product(['name' => 'Tani',    'price' => '50.00']);
    product(['name' => 'Drogi',   'price' => '500.00']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['price' => ['from' => '100', 'to' => '1000']])
        ->call('applyActiveFilters')
        ->assertDontSee('Tani')
        ->assertSee('Drogi');
});

it('auto-applies money filter with formatted money string', function () {
    product(['name' => 'Tani',    'price' => '50.00']);
    product(['name' => 'Drogi',   'price' => '500.00']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['price' => ['from' => '100,00', 'to' => '1 000,00']])
        ->call('applyActiveFilters')
        ->assertDontSee('Tani')
        ->assertSee('Drogi');
});

// ---------------------------------------------------------------------------
// Multiple active filters
// ---------------------------------------------------------------------------

it('auto-applies multiple filters combined', function () {
    product(['name' => 'Laptop',   'category' => 'electronics', 'price' => '999.00']);
    product(['name' => 'Klawiatura', 'category' => 'electronics', 'price' => '150.00']);
    product(['name' => 'Chleb',    'category' => 'food',         'price' => '5.00']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', [
            'category' => 'electronics',
            'price'    => ['from' => '500', 'to' => ''],
        ])
        ->call('applyActiveFilters')
        ->assertSee('Laptop')
        ->assertDontSee('Klawiatura')
        ->assertDontSee('Chleb');
});

// ---------------------------------------------------------------------------
// clearFilters
// ---------------------------------------------------------------------------

it('clearFilters removes all active filters', function () {
    product(['name' => 'Laptop',  'category' => 'electronics']);
    product(['name' => 'Chleb',   'category' => 'food']);

    Livewire::test(DemoProductsTable::class)
        ->set('activeFilters', ['category' => 'electronics'])
        ->call('applyActiveFilters')
        ->assertSee('Laptop')
        ->assertDontSee('Chleb')
        ->call('clearFilters')
        ->assertSee('Laptop')
        ->assertSee('Chleb');
});
