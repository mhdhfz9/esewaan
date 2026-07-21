<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createKontrakSewaanContract(
    string $negeri = 'Johor',
    string $workflow = RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
    ?User $submittedBy = null,
    ?User $adminNegeri = null,
): RentalContract {
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Kontrak Sewaan',
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Alamat ujian',
        'nama_pemilik' => 'Pemilik',
    ]);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => $submittedBy?->id,
        'admin_negeri_user_id' => $adminNegeri?->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => $workflow,
        'kategori_permohonan' => ApplicationCategories::BARU,
        'hq_approved_at' => now(),
        'sah_sehingga' => now()->addMonths(6),
    ]);
}

test('hq approved contract appears in kontrak sewaan for admin hq', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka', 'name' => 'Admin Melaka']);
    $contract = createKontrakSewaanContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, null, $negeriAdmin);
    $contract->premise->update(['nama_ptj' => 'Premis Melaka HQ']);
    $contract->update([
        'tarikh_mula' => now()->subMonths(2),
        'tarikh_tamat' => now()->addMonths(10),
    ]);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('Kontrak Sewaan')
        ->assertSee('Admin Melaka')
        ->assertSee('Premis Melaka HQ')
        ->assertSee('Nama Premis')
        ->assertSee('Tindakan')
        ->assertSee(route('kontrak-sewaan.show', $contract, false))
        ->assertSee('bulan', false)
        ->assertSee('hari lagi');
});

test('hq approved contract appears in kontrak sewaan for admin negeri in same negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor']);
    $contract = createKontrakSewaanContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, null, $admin);
    $contract->premise->update(['nama_ptj' => 'Premis Johor']);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('Premis Johor')
        ->assertSee('Admin Johor')
        ->assertSee('Tindakan')
        ->assertSee(route('kontrak-sewaan.show', $contract, false));
});

test('admin negeri does not see hq approved contract from other negeri in kontrak sewaan', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $otherAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor', 'name' => 'Admin Selangor']);
    createKontrakSewaanContract('Selangor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, null, $otherAdmin);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertDontSee('Admin Selangor');
});

test('pending hq review contract does not appear in kontrak sewaan', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    createKontrakSewaanContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ)
        ->premise
        ->update(['nama_ptj' => 'Premis Belum Disahkan']);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertDontSee('Premis Belum Disahkan');
});

test('hq approved contract is removed from admin negeri status permohonan list', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    createKontrakSewaanContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI)
        ->premise
        ->update(['nama_ptj' => 'Premis Dalam Kontrak Sewaan']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertDontSee('Premis Dalam Kontrak Sewaan');
});

test('sidebar shows kontrak sewaan as top level menu for admin negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Kontrak Sewaan')
        ->assertSee('Senarai Kontrak Sewaan')
        ->assertSee('Pengurusan Permohonan');
});

test('proceeding with draft agreement moves contract to draft preparation, not kontrak sewaan', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka', 'name' => 'Admin Melaka']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createKontrakSewaanContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ, null, $negeriAdmin);
    $contract->premise->update(['nama_ptj' => 'Premis Draf Perjanjian']);
    $contract->update(['hq_approved_at' => null]);

    $this->actingAs($hqAdmin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract))
        ->assertRedirect(route('status-permohonan.index'));

    $contract->refresh();

    expect($contract->hq_approved_at)->not->toBeNull()
        ->and($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($negeriAdmin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertDontSee('Premis Draf Perjanjian');

    $this->actingAs($negeriAdmin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Admin Melaka')
        ->assertSee('Penyediaan Draf Perjanjian');
});

test('kontrak sewaan search filters by nama premis and admin negeri name', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor Khas']);
    $otherAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor Lain']);

    createKontrakSewaanContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, null, $negeriAdmin)
        ->premise
        ->update(['nama_ptj' => 'Premis Carian Khas']);
    createKontrakSewaanContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, null, $otherAdmin)
        ->premise
        ->update(['nama_ptj' => 'Premis Lain']);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index', ['search' => 'Khas']))
        ->assertSuccessful()
        ->assertSee('Admin Johor Khas')
        ->assertSee('Premis Carian Khas')
        ->assertDontSee('Admin Johor Lain')
        ->assertDontSee('Premis Lain');

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index', ['search' => 'Admin Johor Khas', 'partial' => 1]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html',
        ])
        ->assertSuccessful()
        ->assertSee('Premis Carian Khas')
        ->assertDontSee('Premis Lain');
});

test('kontrak sewaan show displays hq jrp checklist with dates for admin negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createKontrakSewaanContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI, null, $admin);
    $contract->update([
        'hq_jrp_checklist' => [
            \App\Support\HqJrpChecklist::KP => true,
            \App\Support\HqJrpChecklist::MOF => false,
            \App\Support\HqJrpChecklist::EPU => false,
            \App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => true,
            \App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => true,
            \App\Support\HqJrpChecklist::AADK_SUBMIT_BPH => true,
            \App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => true,
            'dates' => [
                \App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '2026-07-16',
                \App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => '2026-07-16',
                \App\Support\HqJrpChecklist::AADK_SUBMIT_BPH => '2026-07-16',
                \App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => '2026-07-16',
            ],
        ],
        'remark' => 'Catatan ujian kontrak',
        'semakan_count' => 2,
        'keluasan_mp' => 120.5,
    ]);
    $contract->premise->update([
        'nama_pemilik' => 'Pemilik Ujian',
        'jenis_bangunan' => 'pejabat',
    ]);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.show', $contract))
        ->assertSuccessful()
        ->assertSee('Maklumat Asas')
        ->assertSee('Maklumat Premis & Pemilik', false)
        ->assertSee('Maklumat Kontrak')
        ->assertSee('Kemajuan Permohonan')
        ->assertSee('Permohonan Baru')
        ->assertSee('Semakan HQ')
        ->assertSee('Selesai')
        ->assertSee('Pemilik Ujian')
        ->assertSee('Catatan ujian kontrak')
        ->assertSee('Langkah Tindakan Pegawai Negeri')
        ->assertSee('Pengesahan HQ')
        ->assertSee('Kelulusan Pengurusan Tertinggi')
        ->assertSee('Cawangan Pembangunan AADK menerima ulasan daripada EPU melalui KDN')
        ->assertSee('Cawangan Pembangunan AADK menerima ulasan daripada MOF melalui KDN')
        ->assertSee('16/07/2026')
        ->assertSee('Status Draf Perjanjian')
        ->assertSee('Semakan 2')
        ->assertSee('Pengesahan & Tandatangan', false)
        ->assertSee('Permohonan telah selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan');
});
