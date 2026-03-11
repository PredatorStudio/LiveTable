<?php

use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Workbench\App\Livewire\DemoUsersTable;
use Workbench\App\Models\DemoUser;

uses(FeatureTestCase::class);

it('filters rows matching search phrase', function () {
    DemoUser::create(['imie' => 'Jan',  'nazwisko' => 'Nowak',    'adres' => 'Kraków',   'status' => 'active']);
    DemoUser::create(['imie' => 'Anna', 'nazwisko' => 'Kowalska', 'adres' => 'Warszawa', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->set('search', 'Jan')
        ->assertSee('Jan')
        ->assertDontSee('Anna');
});

it('shows all rows when search is cleared', function () {
    DemoUser::create(['imie' => 'Jan',  'nazwisko' => 'Nowak',    'adres' => 'Kraków',   'status' => 'active']);
    DemoUser::create(['imie' => 'Anna', 'nazwisko' => 'Kowalska', 'adres' => 'Warszawa', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->set('search', 'Jan')
        ->set('search', '')
        ->assertSee('Jan')
        ->assertSee('Anna');
});

it('search resets page to 1', function () {
    Livewire::test(DemoUsersTable::class)
        ->set('page', 3)
        ->set('search', 'foo')
        ->assertSet('page', 1);
});

it('shows no results message when search finds nothing', function () {
    DemoUser::create(['imie' => 'Jan', 'nazwisko' => 'Nowak', 'adres' => 'Kraków', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->set('search', 'xyznonexistent')
        ->assertSee('Brak danych.');
});