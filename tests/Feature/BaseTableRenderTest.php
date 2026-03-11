<?php

use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Workbench\App\Livewire\DemoUsersTable;
use Workbench\App\Models\DemoUser;

uses(FeatureTestCase::class);

it('mounts DemoUsersTable without errors', function () {
    Livewire::test(DemoUsersTable::class)
        ->assertStatus(200);
});

it('shows column headers after mount', function () {
    Livewire::test(DemoUsersTable::class)
        ->assertSee('Imię')
        ->assertSee('Nazwisko');
});

it('renders empty state when no records', function () {
    Livewire::test(DemoUsersTable::class)
        ->assertSee('Brak danych.');
});

it('renders row data when records exist', function () {
    DemoUser::create(['imie' => 'Jan', 'nazwisko' => 'Kowalski', 'adres' => 'Warszawa', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->assertSee('Jan')
        ->assertSee('Kowalski');
});

it('shows pagination info when records exist', function () {
    for ($i = 1; $i <= 3; $i++) {
        DemoUser::create(['imie' => "Użytkownik{$i}", 'nazwisko' => "Test{$i}", 'adres' => "Adres{$i}", 'status' => 'active']);
    }

    Livewire::test(DemoUsersTable::class)
        ->assertSee('3');
});