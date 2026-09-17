<?php

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('login page shows forgot password link', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Lupa Katalaluan?', false)
        ->assertSee(route('password.request', absolute: false), false);
});

test('guest can request password reset link for active user', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'aktif@esewa.test',
        'is_active' => true,
    ]);

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user) {
        return $mail->hasTo($user->email)
            && str_contains($mail->resetUrl, '/set-semula-kata-laluan/');
    });
});

test('inactive user does not receive password reset email but gets generic success message', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'nyahaktif@esewa.test',
        'is_active' => false,
    ]);

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertNothingSent();
});

test('unknown email still shows generic success message without sending mail', function () {
    Mail::fake();

    $this->post(route('password.email'), [
        'email' => 'tiada@esewa.test',
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertNothingSent();
});

test('user can reset password using email link token', function () {
    $user = User::factory()->create([
        'email' => 'reset@esewa.test',
        'password' => 'password-lama',
        'is_active' => true,
    ]);

    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'password-baharu',
        'password_confirmation' => 'password-baharu',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('success');

    $user->refresh();

    expect(Hash::check('password-baharu', $user->password))->toBeTrue();
});

test('reset password page can be opened from emailed link', function () {
    $user = User::factory()->create([
        'email' => 'pautan@esewa.test',
    ]);

    $token = Password::createToken($user);

    $this->get(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]))
        ->assertSuccessful()
        ->assertSee('Set Semula Kata Laluan')
        ->assertSee($user->email, false);
});

test('password reset mail uses custom mailable', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'custom@esewa.test',
    ]);

    $user->sendPasswordResetNotification('test-token');

    Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user) {
        return $mail->hasTo($user->email)
            && str_contains($mail->resetUrl, 'test-token');
    });
});
