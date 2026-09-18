<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\SidebarNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin negeri can open inbox and see application notification', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Inbox Johor',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat Johor',
    ]);

    $contract = RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ]);

    $this->actingAs($admin)
        ->get(route('notifications.index'))
        ->assertSuccessful()
        ->assertSee('Peti Masuk')
        ->assertSee('Premis Inbox Johor')
        ->assertSee(route('application.edit', $contract, false), false);
});

test('header shows inbox icon for admin users', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('id="header-inbox"', false)
        ->assertSee(route('notifications.index'), false);
});

test('admin can mark all inbox notifications as read', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Baca Semua',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat Johor',
    ]);

    RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ]);

    expect(app(SidebarNotificationService::class)->forUser($admin)['inbox_total'])->toBe(1);

    $this->actingAs($admin)
        ->post(route('notifications.mark-all-read'))
        ->assertRedirect(route('notifications.index'))
        ->assertSessionHas('success');

    expect(app(SidebarNotificationService::class)->forUser($admin)['inbox_total'])->toBe(0);
});

test('sidebar notifications poll includes inbox total', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Poll Inbox',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat Johor',
    ]);

    RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ]);

    $this->actingAs($admin)
        ->getJson(route('sidebar-notifications'))
        ->assertSuccessful()
        ->assertJsonPath('inbox_total', 1);
});
