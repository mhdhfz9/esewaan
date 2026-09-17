<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view users index', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    User::factory()->create(['role' => 'admin_negeri', 'email' => 'negeri@example.test', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('Pengurusan Pengguna')
        ->assertSee('Senarai Pengguna')
        ->assertSee('Pendaftaran Pengguna')
        ->assertSee('negeri@example.test')
        ->assertSee('Cari nama, emel, negeri');
});

test('users index search filters results', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    User::factory()->create(['name' => 'Ahmad Zainal', 'email' => 'ahmad@example.test', 'role' => 'admin_negeri']);
    User::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@example.test', 'role' => 'admin_negeri']);

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'Ahmad']))
        ->assertSuccessful()
        ->assertSee('ahmad@example.test')
        ->assertDontSee('siti@example.test');
});

test('users index supports ajax search response', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    User::factory()->create(['name' => 'Carian Ajax', 'email' => 'ajax@example.test', 'role' => 'admin_negeri']);

    $this->actingAs($admin)
        ->getJson(route('users.index', ['search' => 'Ajax']), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertSuccessful()
        ->assertSee('ajax@example.test');
});

test('users index returns table partial for live search requests', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    User::factory()->create(['name' => 'Live Search User', 'email' => 'live@example.test', 'role' => 'admin_negeri']);

    $response = $this->actingAs($admin)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html',
        ])
        ->get(route('users.index', ['search' => 'Live', 'partial' => 1]));

    $response->assertSuccessful();
    expect($response->getContent())->not->toContain('<!DOCTYPE html>');
    expect($response->getContent())->toContain('id="users-count"');
    expect($response->getContent())->toContain('live@example.test');
});

test('users index splits admin and negeri into separate containers', function () {
    $admin = User::factory()->create(['role' => 'admin_hq', 'name' => 'Admin Utama']);
    User::factory()->create(['name' => 'Negeri Johor', 'email' => 'negeri-split@example.test', 'role' => 'admin_negeri', 'negeri' => 'Johor']);

    $html = $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('>Ibu Pejabat</h2>')
        ->and($html)->toContain('>Negeri</h2>')
        ->and($html)->toContain('Admin Utama')
        ->and($html)->toContain('negeri-split@example.test');
});

test('users index search matches role label', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    User::factory()->create(['name' => 'Negeri Satu', 'email' => 'negeri1@example.test', 'role' => 'admin_negeri', 'negeri' => 'Johor']);
    User::factory()->create(['name' => 'Admin Satu', 'email' => 'hq1@example.test', 'role' => 'admin_hq']);

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'Negeri']))
        ->assertSuccessful()
        ->assertSee('negeri1@example.test')
        ->assertDontSee('hq1@example.test');
});

test('users index can sort by name ascending', function () {
    $admin = User::factory()->create(['role' => 'admin_hq', 'name' => 'Zul Admin']);

    User::factory()->create(['name' => 'Ali Pengguna', 'email' => 'ali@example.test', 'role' => 'admin_negeri']);
    User::factory()->create(['name' => 'Bakar Pengguna', 'email' => 'bakar@example.test', 'role' => 'admin_negeri']);

    $response = $this->actingAs($admin)
        ->get(route('users.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful();

    expect($response->getContent())->toContain('Ali Pengguna');
    expect(strpos($response->getContent(), 'Ali Pengguna'))->toBeLessThan(strpos($response->getContent(), 'Bakar Pengguna'));
});

test('guest cannot view users index', function () {
    $this->get(route('users.index'))
        ->assertRedirect(route('login'));
});

test('admin registering user redirects to users index', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertSuccessful()
        ->assertSee('data-password-toggle="register-password"', false)
        ->assertSee('data-password-toggle="register-password-confirm"', false)
        ->assertSee('id="negeri-field"', false);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_negeri',
            'negeri' => 'Johor',
        ])
        ->assertRedirect(route('users.index'));

    expect(User::query()->where('email', 'baru@example.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'baru@example.test')->value('role'))->toBe('admin_negeri');
});

test('admin can register admin role without selecting negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin-baru@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_hq',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    $created = User::query()->where('email', 'admin-baru@example.test')->first();

    expect($created)->not->toBeNull()
        ->and($created->role)->toBe('admin_hq')
        ->and($created->negeri)->toBeNull();
});

test('admin registration requires negeri when role is negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Negeri Baru',
            'email' => 'negeri-baru@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_negeri',
        ])
        ->assertSessionHasErrors('negeri');
});

test('admin can change user role to admin negeri via edit', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create(['role' => 'admin_hq', 'email' => 'hq-role@example.test']);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'admin_negeri',
            'negeri' => 'Johor',
        ])
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->role)->toBe('admin_negeri');
});

test('admin cannot assign ptj user role via edit', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create(['role' => 'admin_negeri', 'email' => 'admin-role@example.test', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'ptj_user',
            'negeri' => 'Johor',
        ])
        ->assertSessionHasErrors('role');

    expect($user->fresh()->role)->toBe('admin_negeri');
});

test('admin cannot change their own role via edit', function () {
    $admin = User::factory()->create(['role' => 'admin_hq', 'email' => 'self@example.test']);

    $this->actingAs($admin)
        ->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin_negeri',
        ])
        ->assertSessionHasErrors('role');
});

test('admin can assign admin hq role via edit', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create(['role' => 'admin_negeri', 'email' => 'hq-new@example.test', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'admin_hq',
        ])
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->role)->toBe('admin_hq');
});

test('admin can view user edit page', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create(['role' => 'admin_negeri', 'name' => 'Ali Negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertSuccessful()
        ->assertSee('Kemaskini profil')
        ->assertSee('Ali Negeri')
        ->assertSee('Peranan')
        ->assertSee('Johor')
        ->assertDontSee('Pengguna PTJ')
        ->assertSee('Negeri')
        ->assertSee('Ibu Pejabat')
        ->assertSee('id="negeri-field"', false)
        ->assertSee('data-password-toggle="admin-reset-password"', false)
        ->assertSee('data-password-toggle="admin-reset-password-confirm"', false);
});

test('admin user edit page hides negeri dropdown', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create(['role' => 'admin_hq', 'name' => 'Admin Target', 'negeri' => null]);

    $html = $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('id="negeri-field"')
        ->and($html)->toContain('hidden')
        ->and($html)->toContain('value="admin_hq"');
});

test('admin can update user profile and reset password', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create([
        'role' => 'admin_negeri',
        'email' => 'lama@example.test',
        'negeri' => 'Johor',
    ]);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Nama Baharu',
            'email' => 'baru@example.test',
            'negeri' => 'Selangor',
            'role' => 'admin_negeri',
            'password' => 'katalaluanbaru',
            'password_confirmation' => 'katalaluanbaru',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->name)->toBe('Nama Baharu')
        ->and($user->email)->toBe('baru@example.test')
        ->and($user->negeri)->toBe('Selangor');
});

test('admin can view user audit trail', function () {
    $admin = User::factory()->create(['role' => 'admin_hq', 'name' => 'Admin']);
    $user = User::factory()->create(['role' => 'admin_negeri', 'name' => 'Pengguna Audit', 'negeri' => 'Johor']);

    \App\Services\ActivityLogger::log(
        $user,
        'login',
        'Log masuk ke sistem.',
        performedBy: $user,
    );

    $this->actingAs($admin)
        ->get(route('users.audit-trail', $user))
        ->assertSuccessful()
        ->assertSee('Audit Trail')
        ->assertSee('Pengguna Audit')
        ->assertSee('Log masuk');
});

test('admin can view audit trail with process log entries', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = \App\Models\Premise::query()->create([
        'nama_ptj' => 'Premis Ujian',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat ujian',
    ]);

    $contract = \App\Models\RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'admin_negeri_user_id' => $user->id,
        'tarikh_mula' => now()->toDateString(),
        'tarikh_tamat' => now()->addYear()->toDateString(),
        'kadar_sewa_bulanan' => 100,
        'status_aktif' => 'aktif',
        'workflow_tahap' => \App\Models\RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
    ]);

    \App\Models\ProcessLog::query()->create([
        'contract_id' => $contract->id,
        'tarikh_tindakan' => now()->toDateString(),
        'jenis_tindakan' => 'Kemaskini status',
        'keterangan' => 'Ujian audit trail',
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('users.audit-trail', $user))
        ->assertSuccessful()
        ->assertSee('Kemaskini status')
        ->assertSee('Proses Kontrak');
});

test('admin negeri is redirected away from dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('status-permohonan.index'));
});

test('admin hq login redirects to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin_hq', 'email' => 'hq-login@example.test']);

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});

test('admin negeri only sees admin negeri users from their negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'email' => 'admin-johor@example.test']);
    User::factory()->create(['role' => 'admin_hq', 'negeri' => 'Johor', 'email' => 'hq@example.test']);
    User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor', 'email' => 'admin-selangor@example.test']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('admin-johor@example.test')
        ->assertDontSee('hq@example.test')
        ->assertDontSee('admin-selangor@example.test');
});

test('admin negeri cannot edit admin hq even in same negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('users.edit', $hq))
        ->assertForbidden();
});

test('admin negeri registration always uses admin negeri role and negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Pengguna Lain',
            'email' => 'lain@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_negeri',
            'negeri' => 'Selangor',
        ])
        ->assertRedirect(route('users.index'));

    $created = User::query()->where('email', 'lain@example.test')->first();

    expect($created)->not->toBeNull()
        ->and($created->negeri)->toBe('Johor')
        ->and($created->role)->toBe('admin_negeri');
});

test('admin negeri registers users with locked negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Negeri Johor',
            'email' => 'negeri-johor@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_negeri',
            'negeri' => 'Johor',
        ])
        ->assertRedirect(route('users.index'));

    expect(User::query()->where('email', 'negeri-johor@example.test')->value('negeri'))->toBe('Johor')
        ->and(User::query()->where('email', 'negeri-johor@example.test')->value('role'))->toBe('admin_negeri');
});

test('admin negeri cannot edit users from other negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $other = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor']);

    $this->actingAs($admin)
        ->get(route('users.edit', $other))
        ->assertForbidden();
});

test('admin hq can deactivate and reactivate user from list', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $target = User::factory()->create(['role' => 'admin_negeri', 'is_active' => true, 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->from(route('users.index'))
        ->patch(route('users.toggle-status', $target))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    expect($target->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->from(route('users.index'))
        ->patch(route('users.toggle-status', $target))
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->is_active)->toBeTrue();
});

test('admin negeri cannot toggle user status', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $target = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->patch(route('users.toggle-status', $target))
        ->assertForbidden();
});

test('deactivated user cannot login', function () {
    $user = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Johor',
        'email' => 'inactive@example.test',
        'is_active' => false,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('admin hq cannot deactivate last active admin hq', function () {
    $admin = User::factory()->create(['role' => 'admin_hq', 'is_active' => true]);

    $this->actingAs($admin)
        ->from(route('users.index'))
        ->patch(route('users.toggle-status', $admin))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('users index shows status toggle only for admin hq', function () {
    $hq = User::factory()->create(['role' => 'admin_hq']);
    User::factory()->create(['role' => 'admin_negeri', 'email' => 'toggle-target@example.test', 'negeri' => 'Johor']);

    $this->actingAs($hq)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('data-user-status-toggle', false)
        ->assertSee('toggle-target@example.test');

    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($negeriAdmin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertDontSee('data-user-status-toggle', false);
});
