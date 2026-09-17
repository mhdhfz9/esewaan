<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view penampilan page', function () {
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($user)
        ->get(route('penampilan.show'))
        ->assertSuccessful()
        ->assertSee('Penampilan')
        ->assertSee('Tema')
        ->assertSee('Saiz fon')
        ->assertSee('Kontras tinggi')
        ->assertDontSee('Ketumpatan')
        ->assertDontSee('Aksesibiliti')
        ->assertSee('Reset ke Default');
});

test('penampilan gear link is visible beside profile in sidebar', function () {
    $user = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($user)
        ->get(route('penampilan.show'))
        ->assertSuccessful()
        ->assertSee(route('penampilan.show'), false)
        ->assertSee('aria-label="Penampilan"', false)
        ->assertDontSee('>Penampilan</span>', false);
});

test('guest cannot view penampilan page', function () {
    $this->get(route('penampilan.show'))
        ->assertRedirect(route('login'));
});
