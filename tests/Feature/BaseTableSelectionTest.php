<?php

use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Workbench\App\Livewire\DemoUsersTable;
use Workbench\App\Models\DemoUser;

uses(FeatureTestCase::class);

it('toggleSelectRow adds row id to selected', function () {
    $user = DemoUser::create(['imie' => 'Jan', 'nazwisko' => 'Nowak', 'adres' => 'Kraków', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->call('toggleSelectRow', (string) $user->id)
        ->assertSet('selected', [(string) $user->id]);
});

it('toggleSelectRow removes row id when already selected', function () {
    $user = DemoUser::create(['imie' => 'Jan', 'nazwisko' => 'Nowak', 'adres' => 'Kraków', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->call('toggleSelectRow', (string) $user->id)
        ->call('toggleSelectRow', (string) $user->id)
        ->assertSet('selected', []);
});

it('selectAllFromQuery sets selectAllQuery to true', function () {
    Livewire::test(DemoUsersTable::class)
        ->call('selectAllFromQuery')
        ->assertSet('selectAllQuery', true);
});

it('clearSelectAllQuery resets selectAllQuery and selected', function () {
    $user = DemoUser::create(['imie' => 'Jan', 'nazwisko' => 'Nowak', 'adres' => 'Kraków', 'status' => 'active']);

    Livewire::test(DemoUsersTable::class)
        ->call('selectAllFromQuery')
        ->set('selected', [(string) $user->id])
        ->call('clearSelectAllQuery')
        ->assertSet('selectAllQuery', false)
        ->assertSet('selected', []);
});
