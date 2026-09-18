<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('header inbox dropdown lists unseen status notifications when badge is positive', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Dropdown Visible',
        'negeri' => 'Selangor',
        'daerah' => 'Petaling',
        'alamat_penuh' => 'Alamat uji',
    ]);

    RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-notification-badge="inbox_total"', false)
        ->assertSee('Premis Dropdown Visible')
        ->assertSee('Dokumen Perjanjian dikembalikan ke Cawangan Pembangunan')
        ->assertSee('header-inbox-item', false);
});

test('sidebar notifications poll returns inbox item payloads', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Poll Item',
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
        ->assertJsonPath('inbox_total', 1)
        ->assertJsonPath('inbox_items.0.message', 'Premis Poll Item · Johor')
        ->assertJsonPath('inbox_items.0.category', 'status_permohonan');
});
