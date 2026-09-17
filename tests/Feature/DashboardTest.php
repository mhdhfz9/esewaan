<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createDashboardContract(
    string $workflow,
    string $negeri = 'Johor',
    ?string $sahSehingga = null,
): RentalContract {
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Dashboard '.uniqid(),
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Alamat ujian',
        'nama_pemilik' => 'Pemilik',
        'kadar_sewa' => 1000,
    ]);

    $hasRealContractDates = filled($sahSehingga);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'tarikh_mula' => $hasRealContractDates
            ? now()->subMonths(2)->toDateString()
            : RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => $hasRealContractDates
            ? $sahSehingga
            : RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 1000,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => $workflow,
        'kategori_permohonan' => ApplicationCategories::BARU,
        'hq_approved_at' => now(),
        'sah_sehingga' => $sahSehingga,
    ]);
}

test('admin negeri is redirected away from dashboard', function () {
    $user = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('status-permohonan.index'));
});

test('dashboard shows accurate counts based on current workflow data', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    createDashboardContract(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    createDashboardContract(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    createDashboardContract(RentalContract::WORKFLOW_SEMAKAN_PUU);
    createDashboardContract(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, 'Johor', now()->addMonths(4)->toDateString());
    createDashboardContract(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, 'Johor', now()->addMonths(20)->toDateString());

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertSuccessful()
        ->assertViewHas('permohonanBaharu', 1)
        ->assertViewHas('progressPermohonan', 2)
        ->assertViewHas('kontrakAktif', 2)
        ->assertViewHas('kontrakLapanBulan', 1)
        ->assertSee('Permohonan Baharu')
        ->assertSee('Dalam Tindakan')
        ->assertSee('Status Tindakan')
        ->assertSee('Belum Terima Permohonan')
        ->assertSee('Sedia Draf Perjanjian')
        ->assertSee('Semakan PUU')
        ->assertSee('Tindakan Mati Setem')
        ->assertSee('Kontrak Aktif')
        ->assertSee('Kontrak &le; 8 Bulan', false)
        ->assertDontSee('Dalam Tindakan – Mengikut Status')
        ->assertDontSee('Permohonan Terkini')
        ->assertDontSee('Status Aliran Kerja Permohonan')
        ->assertDontSee('Taburan permohonan mengikut peringkat proses');

    $breakdown = $response->viewData('dalamTindakanByStatus');

    expect($breakdown)->toHaveCount(13)
        ->and(collect($breakdown)->firstWhere('key', \App\Support\StatusTindakan::SEDIA_DRAF_PERJANJIAN)['count'])->toBe(1)
        ->and(collect($breakdown)->firstWhere('key', \App\Support\StatusTindakan::SEMAKAN_PUU)['count'])->toBe(1)
        ->and(collect($breakdown)->sum('count'))->toBe(2);
});

test('dashboard dalam tindakan card links to status breakdown section', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('href="#dalam-tindakan"', false)
        ->assertSee('id="dalam-tindakan"', false);
});

test('dashboard excludes superseded contracts from active count', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $active = createDashboardContract(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, 'Johor', now()->addMonths(20)->toDateString());
    $superseded = createDashboardContract(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, 'Johor', now()->addMonths(20)->toDateString());
    $superseded->update(['superseded_at' => now()]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertViewHas('kontrakAktif', 1);
});
