<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot poll sidebar notifications', function () {
    $this->getJson(route('sidebar-notifications'))
        ->assertUnauthorized();
});

test('sidebar notifications endpoint returns current badge counts for admin negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Poll',
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

    $this->actingAs($admin)
        ->getJson(route('sidebar-notifications'))
        ->assertSuccessful()
        ->assertJson([
            'list_menu' => 1,
            'kontrak_sewaan' => 1,
        ]);
});

test('sidebar notifications endpoint uses max count for admin hq list menu badge', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis HQ Poll',
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

    $this->actingAs($hqAdmin)
        ->getJson(route('sidebar-notifications'))
        ->assertSuccessful()
        ->assertJson([
            'list_menu' => 1,
            'kontrak_sewaan' => 0,
        ]);
});

test('authenticated layout includes sidebar notification polling metadata for admins', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('name="sidebar-notifications-url"', false)
        ->assertSee(route('sidebar-notifications'), false);
});
