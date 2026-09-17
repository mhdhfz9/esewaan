<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated layout includes global loading overlay', function () {
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($user)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('id="global-loading-overlay"', false)
        ->assertSee('Memuatkan')
        ->assertSee('Jangan tutup atau muat semula halaman')
        ->assertSee('window.AppLoading', false)
        ->assertSee('HTMLFormElement.prototype.submit', false)
        ->assertDontSee('window.fetch = function', false);
});

test('login page includes global loading overlay', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('id="global-loading-overlay"', false)
        ->assertSee('Sila tunggu sebentar. Jangan tutup atau muat semula halaman.')
        ->assertSee('data-no-global-loader', false);
});

test('logout form skips global loading overlay', function () {
    $user = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('data-no-global-loader', false);
});
