<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\SidebarNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin negeri sidebar shows status permohonan and kontrak sewaan counts separately', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $proceedPremise = Premise::query()->create([
        'nama_ptj' => 'Premis Proceed',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat Johor',
    ]);

    RentalContract::query()->create([
        'premise_id' => $proceedPremise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ]);

    $kontrakPremise = Premise::query()->create([
        'nama_ptj' => 'Premis Johor',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat Johor',
    ]);

    RentalContract::query()->create([
        'premise_id' => $kontrakPremise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => now()->subMonths(2)->toDateString(),
        'tarikh_tamat' => now()->addYear()->toDateString(),
        'kadar_sewa_bulanan' => 500,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'hq_approved_at' => now(),
    ]);

    $counts = app(SidebarNotificationService::class)->forUser($admin);

    expect($counts['status_permohonan'])->toBe(1)
        ->and($counts['kontrak_sewaan'])->toBe(1)
        ->and($counts['admin_pending_review'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Status Permohonan')
        ->assertSee('Permohonan baharu menunggu semakan', false)
        ->assertSee('Kontrak baharu dalam senarai', false);
});

test('admin hq sidebar shows pending hq review count', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis HQ',
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
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
    ]);

    $counts = app(SidebarNotificationService::class)->forUser($admin);

    expect($counts['status_permohonan'])->toBe(1)
        ->and($counts['admin_pending_review'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Senarai Permohonan')
        ->assertSee('Permohonan baharu menunggu semakan', false);
});

test('admin hq sidebar shows pending withdrawal count', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Withdrawal',
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
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
    ]);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $counts = app(SidebarNotificationService::class)->forUser($hqAdmin);

    expect($counts['hq_pending_withdrawal'])->toBe(1);
});

test('admin negeri does not count pending items from other negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Selangor',
        'negeri' => 'Selangor',
        'daerah' => 'Selangor',
        'alamat_penuh' => 'Alamat Selangor',
    ]);

    RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => now()->subMonths(2)->toDateString(),
        'tarikh_tamat' => now()->addYear()->toDateString(),
        'kadar_sewa_bulanan' => 500,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'hq_approved_at' => now(),
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

    $counts = app(SidebarNotificationService::class)->forUser($admin);

    expect($counts['status_permohonan'])->toBe(0)
        ->and($counts['kontrak_sewaan'])->toBe(0)
        ->and($counts['admin_pending_review'])->toBe(0);
});

test('notification count decreases after admin hq opens application review', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Baharu HQ',
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
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
    ]);

    $service = app(SidebarNotificationService::class);

    expect($service->forUser($admin)['status_permohonan'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful();

    expect($service->forUser($admin)['status_permohonan'])->toBe(0);
});

test('notification count decreases after admin views kontrak sewaan', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Kontrak Admin',
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
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'hq_approved_at' => now(),
    ]);

    $service = app(SidebarNotificationService::class);

    expect($service->forUser($admin)['kontrak_sewaan'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.show', $contract))
        ->assertSuccessful();

    expect($service->forUser($admin)['kontrak_sewaan'])->toBe(0);
});

test('notification count increases when a new application enters the pending queue', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Pertama',
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

    $service = app(SidebarNotificationService::class);

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertSuccessful();

    expect($service->forUser($admin)['status_permohonan'])->toBe(0);

    $newPremise = Premise::query()->create([
        'nama_ptj' => 'Premis Baharu',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat baharu',
    ]);

    RentalContract::query()->create([
        'premise_id' => $newPremise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ]);

    expect($service->forUser($admin)['status_permohonan'])->toBe(1)
        ->and($service->forUser($admin)['kontrak_sewaan'])->toBe(0);
});
