<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeded admin hq can login', function () {
    $this->seed(UserSeeder::class);

    $this->post(route('login.store'), [
        'email' => 'admin@esewa.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs(User::query()->where('email', 'admin@esewa.test')->first());
});

test('seeded admin negeri can login', function () {
    $this->seed(UserSeeder::class);

    $this->post(route('login.store'), [
        'email' => 'negeri@esewa.test',
        'password' => 'password',
    ])->assertRedirect(route('status-permohonan.index'));
});
