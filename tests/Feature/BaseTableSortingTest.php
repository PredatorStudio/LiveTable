<?php

use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Workbench\App\Livewire\DemoUsersTable;
use Workbench\App\Models\DemoUser;

uses(FeatureTestCase::class);

it('sorts ascending on first column click', function () {
    Livewire::test(DemoUsersTable::class)
        ->call('sort', 'imie')
        ->assertSet('sortBy', 'imie')
        ->assertSet('sortDir', 'asc');
});

it('sorts descending on second click of same column', function () {
    Livewire::test(DemoUsersTable::class)
        ->call('sort', 'imie')
        ->call('sort', 'imie')
        ->assertSet('sortBy', 'imie')
        ->assertSet('sortDir', 'desc');
});

it('resets to asc when switching to different column', function () {
    Livewire::test(DemoUsersTable::class)
        ->call('sort', 'imie')
        ->call('sort', 'imie') // now desc
        ->call('sort', 'nazwisko') // switch to different column
        ->assertSet('sortBy', 'nazwisko')
        ->assertSet('sortDir', 'asc');
});

it('ignores sort call for column that does not exist', function () {
    Livewire::test(DemoUsersTable::class)
        ->assertSet('sortBy', '')
        ->call('sort', 'nonexistent_column')
        ->assertSet('sortBy', '');
});

it('sort resets page to 1', function () {
    Livewire::test(DemoUsersTable::class)
        ->set('page', 3)
        ->call('sort', 'imie')
        ->assertSet('page', 1);
});

it('sort returns rows in ascending order', function () {
    DemoUser::create(['imie' => 'Zbigniew', 'nazwisko' => 'A', 'adres' => 'X', 'status' => 'active']);
    DemoUser::create(['imie' => 'Adam',     'nazwisko' => 'B', 'adres' => 'Y', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->call('sort', 'imie')
        ->assertSeeInOrder(['Adam', 'Zbigniew']);
});