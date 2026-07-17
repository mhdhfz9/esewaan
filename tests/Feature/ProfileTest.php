<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view own profile', function () {
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful()
        ->assertSee('Profil Saya')
        ->assertSee($user->email)
        ->assertSee('Kemaskini profil')
        ->assertSee('Johor')
        ->assertDontSee('Padam akaun');
});

test('admin profile pages omit negeri field', function () {
    $user = User::factory()->create(['role' => 'admin_hq', 'negeri' => null]);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful()
        ->assertSee('Profil Saya')
        ->assertDontSee('>Negeri</dt>', false);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('Kemaskini profil saya')
        ->assertDontSee('name="negeri"', false)
        ->assertDontSee('id="negeri"', false);
});

test('guest cannot view profile', function () {
    $this->get(route('profile.show'))
        ->assertRedirect(route('login'));
});

test('admin negeri can update own profile without changing negeri', function () {
    $user = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Johor',
        'email' => 'lama@example.test',
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Nama Baharu',
            'email' => 'baru@example.test',
            'negeri' => 'Selangor',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])
        ->assertRedirect(route('profile.show'));

    $user->refresh();

    expect($user->name)->toBe('Nama Baharu')
        ->and($user->email)->toBe('baru@example.test')
        ->and($user->negeri)->toBe('Johor');
});

test('admin negeri cannot change negeri on own profile', function () {
    $user = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Perak',
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'negeri' => 'Johor',
        ])
        ->assertRedirect(route('profile.show'));

    expect($user->fresh()->negeri)->toBe('Perak');
});

test('sidebar profile links to profile page', function () {
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($user)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee(route('profile.show'), false);
});

test('profile destroy route is not available', function () {
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($user)
        ->delete('/profil')
        ->assertMethodNotAllowed();
});
