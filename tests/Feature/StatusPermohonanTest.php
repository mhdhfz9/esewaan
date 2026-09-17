<?php

use App\Mail\ApplicationApprovedByHqNotification;
use App\Mail\ApplicationSubmittedToAdminNegeriNotification;
use App\Mail\ApplicationSubmittedToHqNotification;
use App\Models\ContractDocument;
use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createStatusListContract(
    string $negeri = 'Johor',
    string $workflow = RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ?User $submittedBy = null,
): RentalContract {
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Status',
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Alamat',
        'nama_pemilik' => 'Pemilik',
    ]);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => $submittedBy?->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => $workflow,
        'kategori_permohonan' => ApplicationCategories::BARU,
    ]);
}

test('admin negeri can delete permohonan in their negeri from status list', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor');

    $this->actingAs($admin)
        ->delete(route('status-permohonan.destroy', $contract), [
            'delete_reason' => 'Permohonan dibuat secara tidak sengaja dan perlu dibatalkan.',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect(RentalContract::query()->count())->toBe(0)
        ->and(RentalContract::withTrashed()->count())->toBe(1)
        ->and(Premise::query()->count())->toBe(1);

    $deleted = RentalContract::withTrashed()->first();
    expect($deleted->delete_reason)->toBe('Permohonan dibuat secara tidak sengaja dan perlu dibatalkan.')
        ->and($deleted->deleted_by_user_id)->toBe($admin->id);
});

test('admin negeri must provide delete reason when deleting permohonan', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor');

    $this->actingAs($admin)
        ->from(route('status-permohonan.index'))
        ->delete(route('status-permohonan.destroy', $contract), [
            'delete_reason' => 'pendek',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHasErrors('delete_reason');

    expect(RentalContract::query()->count())->toBe(1);
});

test('admin hq can view deleted permohonan in history tab', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor');
    $contract->premise->update(['nama_ptj' => 'Premis Sejarah Ibu Pejabat']);

    $this->actingAs($negeriAdmin)
        ->delete(route('status-permohonan.destroy', $contract), [
            'delete_reason' => 'Maklumat premis tidak tepat dan perlu diisi semula.',
        ]);

    $this->actingAs($hqAdmin)
        ->get(route('status-permohonan.index', ['tab' => 'history']))
        ->assertSuccessful()
        ->assertSee('Sejarah')
        ->assertSee('Premis Sejarah Ibu Pejabat')
        ->assertSee('Maklumat premis tidak tepat dan perlu diisi semula.')
        ->assertSee('Admin Johor');
});

test('admin negeri cannot delete permohonan after sent to hq', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->delete(route('status-permohonan.destroy', $contract), [
            'delete_reason' => 'Cuba padam selepas dihantar ke Ibu Pejabat.',
        ])
        ->assertForbidden();

    expect(RentalContract::query()->count())->toBe(1);
});

test('admin negeri can request withdrawal for hq submitted permohonan', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    $contract->refresh();

    expect($contract->withdrawal_status)->toBe(RentalContract::WITHDRAWAL_PENDING)
        ->and($contract->withdrawal_reason)->toBe('Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.')
        ->and($contract->withdrawal_requested_by_user_id)->toBe($admin->id)
        ->and($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
});

test('admin hq can approve withdrawal request and return permohonan to negeri', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $this->actingAs($hqAdmin)
        ->post(route('status-permohonan.resolve-withdrawal', $contract), [
            'decision' => 'approve',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    $contract->refresh();

    expect($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI)
        ->and($contract->withdrawal_status)->toBe(RentalContract::WITHDRAWAL_APPROVED)
        ->and($contract->withdrawal_resolved_by_user_id)->toBe($hqAdmin->id);
});

test('admin hq can reject withdrawal request', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $this->actingAs($hqAdmin)
        ->post(route('status-permohonan.resolve-withdrawal', $contract), [
            'decision' => 'reject',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    $contract->refresh();

    expect($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ)
        ->and($contract->withdrawal_status)->toBe(RentalContract::WITHDRAWAL_REJECTED);
});

test('admin hq cannot approve permohonan while withdrawal is pending', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $this->actingAs($hqAdmin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract))
        ->assertForbidden();
});

test('admin hq sees withdrawal notification icon on pending withdrawal permohonan', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor Tarik']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['admin_negeri_user_id' => $negeriAdmin->id]);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $this->actingAs($hqAdmin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Admin Johor Tarik')
        ->assertSee('Negeri memohon untuk tarik semula')
        ->assertDontSee('Menunggu kelulusan tarik semula')
        ->assertSee('Lihat Permohonan')
        ->assertSee(route('status-permohonan.review', $contract, false))
        ->assertSee(route('status-permohonan.resolve-withdrawal', $contract, false));

    $this->actingAs($hqAdmin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Permohonan Tarik Semula')
        ->assertSee('Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.')
        ->assertSee('Luluskan Tarik Semula')
        ->assertSee('Tolak Tarik Semula')
        ->assertDontSee('Sahkan Permohonan');
});

test('admin negeri sees menunggu kelulusan tarik semula label for own withdrawal request', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $this->actingAs($negeriAdmin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Menunggu kelulusan tarik semula')
        ->assertDontSee('Negeri memohon untuk tarik semula');
});

test('status permohonan sync endpoint returns fingerprint for admin hq', function () {
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($hqAdmin)
        ->getJson(route('status-permohonan.sync'))
        ->assertSuccessful()
        ->assertJsonStructure(['fingerprint']);
});

test('status permohonan sync fingerprint changes after withdrawal request', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $before = $this->actingAs($hqAdmin)
        ->getJson(route('status-permohonan.sync'))
        ->assertSuccessful()
        ->json('fingerprint');

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan Ibu Pejabat.',
        ]);

    $after = $this->actingAs($hqAdmin)
        ->getJson(route('status-permohonan.sync'))
        ->assertSuccessful()
        ->json('fingerprint');

    expect($after)->not->toBe($before);
});

test('status permohonan index includes realtime sync metadata', function () {
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($hqAdmin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('data-sync-url="'.route('status-permohonan.sync').'"', false);
});

test('guest cannot access status permohonan sync endpoint', function () {
    $this->getJson(route('status-permohonan.sync'))
        ->assertUnauthorized();
});

test('admin negeri cannot delete permohonan from other negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Selangor');

    $this->actingAs($admin)
        ->delete(route('status-permohonan.destroy', $contract))
        ->assertForbidden();

    expect(RentalContract::query()->count())->toBe(1);
});

test('admin hq cannot delete submitted permohonan from status list', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->delete(route('status-permohonan.destroy', $contract))
        ->assertForbidden();

    expect(RentalContract::query()->count())->toBe(1);
});

test('admin hq sees senarai permohonan with semak and sahkan actions', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Senarai Permohonan')
        ->assertSee(route('status-permohonan.review', $contract, false))
        ->assertDontSee('Muat Naik Dokumen')
        ->assertDontSee('Kemaskini Status');
});

test('admin hq can review application content', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->premise->update(['nama_ptj' => 'Premis Semakan Ibu Pejabat']);
    $contract->update([
        'sah_sehingga' => now()->addMonths(3),
        'admin_proceed_progress' => [
            \App\Support\AdminProceedSteps::STEP_SURAT_NIAT => [
                'completed' => true,
                'confirmed_accurate' => true,
                'confirmed_promis' => true,
                'notes' => 'Surat niat telah dikeluarkan kepada pemilik.',
                'no_rujukan' => 'AADK/BKP/PB 200-3/02',
                'tarikh_surat' => '2026-01-15',
                'completed_at' => now()->toIso8601String(),
            ],
            \App\Support\AdminProceedSteps::STEP_SURAT_AGENSI => [
                'completed' => true,
                'confirmed_accurate' => true,
                'confirmed_promis' => true,
                'notes' => 'Surat agensi lengkap.',
                'no_rujukan' => 'AADK/AGENSI/01',
                'tarikh_surat' => '2026-01-20',
                'agencies' => collect(\App\Support\AdminProceedSteps::agencyKeys())
                    ->mapWithKeys(fn (string $key): array => [$key => true])
                    ->all(),
                'agency_dates' => collect(\App\Support\AdminProceedSteps::agenciesRequiringDate())
                    ->mapWithKeys(fn (string $key): array => [$key => '2026-01-15'])
                    ->all(),
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Maklumat Asas')
        ->assertSee('Maklumat Premis & Pemilik', false)
        ->assertSee('Premis Semakan Ibu Pejabat')
        ->assertSee('Langkah Tindakan Pegawai Negeri')
        ->assertSee('Pengesahan Ibu Pejabat')
        ->assertSee('Surat niat telah dikeluarkan kepada pemilik.')
        ->assertSee('Surat agensi lengkap.')
        ->assertSee('No. Rujukan')
        ->assertSee('AADK/BKP/PB 200-3/02')
        ->assertSee('Tarikh Surat')
        ->assertSee('Saya mengesahkan surat niat telah dihantar ke premis')
        ->assertSee('Surat tawaran pemilik premis telah diterima dan dimuat naik ke PROMIS')
        ->assertSee('Saya mengesahkan maklumat ini tepat dan benar')
        ->assertSee('Maklumat ini telah dimuat naik ke dalam sistem PROMIS.')
        ->assertSee('Saya mengesahkan langkah ini telah selesai dilaksanakan.')
        ->assertSee('Jabatan Bomba dan Penyelamat Malaysia')
        ->assertSee('Mengemukakan Borang JRP kepada KDN untuk mendapatkan ulasan/kelulusan daripada agensi berikut')
        ->assertSee('Cawangan Pembangunan AADK terima surat kelulusan daripada BPH');
});

test('admin hq can approve without ticking jrp checklist items', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.approve', $contract), ['jrp_checklist' => []])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN)
        ->and($contract->fresh()->hq_jrp_checklist[\App\Support\HqJrpChecklist::KP])->toBeFalse();
});

test('admin hq can approve with partial jrp checklist including mof', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract, withMofOrEpu: true))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    $saved = $contract->fresh()->hq_jrp_checklist;

    expect($saved[\App\Support\HqJrpChecklist::MOF])->toBeTrue()
        ->and($saved[\App\Support\HqJrpChecklist::KP])->toBeFalse();
});

test('jrp normalize input preserves submitted values and records dates for items 4 to 7', function () {
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $normalized = \App\Support\HqJrpChecklist::normalizeInput([
        \App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '1',
        \App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => '1',
        \App\Support\HqJrpChecklist::AADK_SUBMIT_BPH => '1',
        \App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => '1',
        'dates' => [
            \App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '2026-07-16',
            \App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => '2026-07-16',
            \App\Support\HqJrpChecklist::AADK_SUBMIT_BPH => '2026-07-16',
            \App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => '2026-07-16',
        ],
    ], $contract);

    expect($normalized[\App\Support\HqJrpChecklist::KP])->toBeFalse()
        ->and($normalized[\App\Support\HqJrpChecklist::MOF])->toBeFalse()
        ->and($normalized[\App\Support\HqJrpChecklist::EPU])->toBeFalse()
        ->and($normalized[\App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS])->toBeTrue()
        ->and($normalized[\App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS])->toBeTrue()
        ->and($normalized['dates'][\App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS])->toBe('2026-07-16')
        ->and($normalized['dates'][\App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL])->toBe('2026-07-16')
        ->and($normalized['dates'][\App\Support\HqJrpChecklist::KP])->toBeNull();
});

test('hq review page shows date fields for checklist items 4 to 7', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $html = $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->getContent();

    foreach (\App\Support\HqJrpChecklist::keysRequiringDate() as $key) {
        expect($html)->toContain('data-jrp-date-for="'.$key.'"')
            ->and($html)->toContain('name="jrp_checklist[dates]['.$key.']"');
    }

    expect($html)->toContain('data-jrp-date-for="kp"')
        ->and($html)->toContain('data-jrp-date-for="mof_negeri_budget_office"')
        ->and($html)->toContain('data-jrp-date-for="epu_jpm_planning"');
});

test('admin hq approval stores dates for checked jrp checklist items 4 to 7', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract))
        ->assertRedirect(route('status-permohonan.index'));

    $saved = $contract->fresh()->hq_jrp_checklist;

    expect($saved['dates'][\App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS])->toBe('2026-07-16')
        ->and($saved['dates'][\App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS])->toBe('2026-07-16')
        ->and($saved['dates'][\App\Support\HqJrpChecklist::AADK_SUBMIT_BPH])->toBe('2026-07-16')
        ->and($saved['dates'][\App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL])->toBe('2026-07-16');
});

test('hq review page always shows all seven jrp checklist items', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['keluasan_mp' => 300, 'kategori_permohonan' => ApplicationCategories::LANJUTAN]);

    $response = $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful();

    foreach (\App\Support\HqJrpChecklist::allKeys() as $key) {
        $response->assertSee(\App\Support\HqJrpChecklist::definitions()[$key], false);
    }

    expect(\App\Support\HqJrpChecklist::applicableKeys($contract))->toHaveCount(7);
});

test('hq review page auto ticks epu when keluasan exceeds 465 mps', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['keluasan_mp' => 500]);

    $html = $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->getContent();

    preg_match('/<input\b[^>]*data-jrp-key="epu_jpm_planning"[^>]*>/', $html, $epuInput);
    preg_match('/<input\b[^>]*data-jrp-key="kp"[^>]*>/', $html, $kpInput);

    expect(\App\Support\HqJrpChecklist::requiresEpuCheckbox($contract))->toBeTrue()
        ->and($epuInput[0] ?? '')->toContain('checked')
        ->and($kpInput[0] ?? '')->toContain('checked')
        ->and($kpInput[0] ?? '')->not->toContain('disabled');
});

test('hq review page auto ticks kelulusan pengurusan tertinggi for baru applications', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['keluasan_mp' => 465, 'kategori_permohonan' => ApplicationCategories::BARU]);

    $html = $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->getContent();

    preg_match('/<input\b[^>]*data-jrp-key="epu_jpm_planning"[^>]*>/s', $html, $epuInput);
    preg_match('/<input\b[^>]*data-jrp-key="kp"[^>]*>/s', $html, $kpInput);

    expect(\App\Support\HqJrpChecklist::requiresEpuCheckbox($contract))->toBeFalse()
        ->and($epuInput[0] ?? '')->not->toContain('checked')
        ->and($kpInput[0] ?? '')->toContain('checked')
        ->and($kpInput[0] ?? '')->not->toContain('disabled');
});

test('hq review page does not auto tick kelulusan pengurusan tertinggi for lanjutan applications', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['kategori_permohonan' => ApplicationCategories::LANJUTAN]);

    $html = $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->getContent();

    preg_match('/<input\b[^>]*data-jrp-key="kp"[^>]*>/s', $html, $kpInput);

    expect($kpInput[0] ?? '')->not->toContain('checked');
});

test('admin hq can approve application after review', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN)
        ->and($contract->fresh()->hq_jrp_checklist)->not->toBeNull();
});

test('approving application by hq sends email notification to admin hq and relevant admin negeri user', function () {
    Mail::fake();
    config(['mail.test_recipient' => null]);

    $hqAdmin = User::factory()->create([
        'role' => 'admin_hq',
        'name' => 'Admin',
        'email' => 'hq-active@example.test',
        'is_active' => true,
    ]);
    User::factory()->create([
        'role' => 'admin_hq',
        'is_active' => false,
        'email' => 'hq-inactive@example.test',
    ]);
    $negeriAdmin = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Melaka',
        'name' => 'Negeri Melaka',
        'email' => 'negeri-melaka@example.test',
        'is_active' => true,
    ]);
    User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Johor',
        'email' => 'negeri-johor@example.test',
        'is_active' => true,
    ]);

    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['admin_negeri_user_id' => $negeriAdmin->id]);

    $this->actingAs($hqAdmin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    Mail::assertSent(ApplicationApprovedByHqNotification::class, function (ApplicationApprovedByHqNotification $mail) use ($negeriAdmin, $hqAdmin, $contract): bool {
        $html = $mail->render();

        return $mail->hasTo($negeriAdmin->email)
            && $mail->contract->is($contract->fresh())
            && $mail->approvedBy->is($hqAdmin)
            && str_contains($html, 'Penyediaan Draf Perjanjian')
            && ! str_contains($html, 'No. Fail Rujukan')
            && ! str_contains($html, 'Menunggu maklum balas daripada PTJ');
    });

    Mail::assertSent(ApplicationApprovedByHqNotification::class, function (ApplicationApprovedByHqNotification $mail) use ($hqAdmin, $contract): bool {
        return $mail->hasTo($hqAdmin->email)
            && $mail->contract->is($contract->fresh())
            && $mail->approvedBy->is($hqAdmin);
    });

    Mail::assertNotSent(ApplicationApprovedByHqNotification::class, function (ApplicationApprovedByHqNotification $mail): bool {
        return $mail->hasTo('negeri-johor@example.test') || $mail->hasTo('hq-inactive@example.test');
    });

    Mail::assertSent(ApplicationApprovedByHqNotification::class, 2);
});

test('approving application by hq sends email to configured test recipient when set', function () {
    Mail::fake();
    config(['mail.test_recipient' => 'muhammad.hafiz@aadk.gov.my']);

    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $negeriAdmin = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Melaka',
        'email' => 'negeri-melaka@example.test',
        'is_active' => true,
    ]);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['admin_negeri_user_id' => $negeriAdmin->id]);

    $this->actingAs($hqAdmin)
        ->post(route('status-permohonan.approve', $contract), hqJrpChecklistFor($contract))
        ->assertRedirect(route('status-permohonan.index'));

    Mail::assertSent(ApplicationApprovedByHqNotification::class, function (ApplicationApprovedByHqNotification $mail): bool {
        return $mail->hasTo('muhammad.hafiz@aadk.gov.my');
    });

    Mail::assertSent(ApplicationApprovedByHqNotification::class, 2);
});

test('status list displays admin negeri column', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor', 'name' => 'Admin Selangor']);
    $contract = createStatusListContract('Selangor');
    $contract->update(['admin_negeri_user_id' => $admin->id]);
    $contract->premise->update(['nama_ptj' => 'Premis Selangor']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Negeri')
        ->assertDontSee('Pengguna PTJ')
        ->assertSee('Admin Selangor');
});

test('status list search filters by nama premis', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $matchAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Premis Khas']);
    $otherAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Premis Lain']);

    $match = createStatusListContract('Johor');
    $match->update(['admin_negeri_user_id' => $matchAdmin->id]);
    $match->premise->update(['nama_ptj' => 'AADK Daerah Khas']);

    $other = createStatusListContract('Johor');
    $other->update(['admin_negeri_user_id' => $otherAdmin->id]);
    $other->premise->update(['nama_ptj' => 'AADK Daerah Lain']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index', ['search' => 'Khas']))
        ->assertSuccessful()
        ->assertSee('Admin Premis Khas')
        ->assertDontSee('Admin Premis Lain');
});

test('status list search matches displayed status label', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $match = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $match->premise->update(['negeri' => 'Johor']);

    $other = createStatusListContract('Johor');
    $other->premise->update(['nama_ptj' => 'Premis Tindakan']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index', ['search' => 'Menunggu semakan Ibu Pejabat']))
        ->assertSuccessful()
        ->assertSee('Menunggu semakan Ibu Pejabat')
        ->assertDontSee('Menunggu langkah tindakan');
});

test('status list live search returns table partial only', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Partial']);
    $contract = createStatusListContract('Johor');
    $contract->update(['admin_negeri_user_id' => $admin->id]);
    $contract->premise->update(['nama_ptj' => 'Premis Partial']);

    $response = $this->actingAs($admin)
        ->get(route('status-permohonan.index', ['search' => 'Partial', 'partial' => 1]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html',
        ]);

    $response->assertSuccessful();
    expect($response->getContent())->not->toContain('<!DOCTYPE html>');
    expect($response->getContent())->toContain('Admin Partial');
});

test('admin hq cannot see pending proceed permohonan in status list', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI);
    $contract->premise->update(['nama_ptj' => 'Premis Rahsia Negeri']);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertDontSee('Premis Rahsia Negeri');
});

test('admin hq can see permohonan after admin negeri submits to hq', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka', 'name' => 'Admin Melaka HQ']);
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $contract->update(['admin_negeri_user_id' => $negeriAdmin->id]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Admin Melaka HQ')
        ->assertSee('Menunggu semakan Ibu Pejabat');
});

test('status list shows edit and hantar ke hq actions when ready', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor');

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee(route('application.edit', $contract, false))
        ->assertDontSee('Teruskan', false)
        ->assertSee(route('status-permohonan.destroy', $contract, false));
});

test('admin negeri can send completed application to hq from status list', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor');

    foreach (\App\Support\AdminProceedSteps::activeStepsFor($contract) as $step) {
        $key = \App\Support\AdminProceedSteps::keyForStep($step);
        $stepData = [
            'completed' => '1',
            'confirmed_accurate' => '1',
            'confirmed_promis' => '1',
            'notes' => 'Catatan '.$step,
            'no_rujukan' => 'AADK/REF/'.$step,
            'tarikh_surat' => '2026-01-15',
        ];

        if ($key === \App\Support\AdminProceedSteps::STEP_SURAT_AGENSI) {
            $stepData['agencies'] = collect(\App\Support\AdminProceedSteps::agencyKeys())
                ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '1'])
                ->all();
            $stepData['agency_dates'] = collect(\App\Support\AdminProceedSteps::agenciesRequiringDate())
                ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '2026-01-15'])
                ->all();
        }

        $this->actingAs($admin)->put(route('admin-proceed.update', $contract), [
            'current_step' => $step,
            'proceed_steps' => [$key => $stepData],
        ]);
        $contract->refresh();
    }

    $this->actingAs($admin)
        ->post(route('status-permohonan.submit-hq', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
});

test('submitting application to hq sends email notification to active admin hq users', function () {
    Mail::fake();
    config(['mail.test_recipient' => null]);

    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor']);
    $hqActive = User::factory()->create(['role' => 'admin_hq', 'is_active' => true, 'email' => 'hq-active@example.test']);
    User::factory()->create(['role' => 'admin_hq', 'is_active' => false, 'email' => 'hq-inactive@example.test']);
    User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor', 'email' => 'negeri@example.test']);

    $contract = createStatusListContract('Johor');

    foreach (\App\Support\AdminProceedSteps::activeStepsFor($contract) as $step) {
        $key = \App\Support\AdminProceedSteps::keyForStep($step);
        $stepData = [
            'completed' => '1',
            'confirmed_accurate' => '1',
            'confirmed_promis' => '1',
            'notes' => 'Catatan '.$step,
            'no_rujukan' => 'AADK/REF/'.$step,
            'tarikh_surat' => '2026-01-15',
        ];

        if ($key === \App\Support\AdminProceedSteps::STEP_SURAT_AGENSI) {
            $stepData['agencies'] = collect(\App\Support\AdminProceedSteps::agencyKeys())
                ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '1'])
                ->all();
            $stepData['agency_dates'] = collect(\App\Support\AdminProceedSteps::agenciesRequiringDate())
                ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '2026-01-15'])
                ->all();
        }

        $this->actingAs($admin)->put(route('admin-proceed.update', $contract), [
            'current_step' => $step,
            'proceed_steps' => [$key => $stepData],
        ]);
        $contract->refresh();
    }

    $this->actingAs($admin)
        ->post(route('status-permohonan.submit-hq', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    Mail::assertSent(ApplicationSubmittedToHqNotification::class, function (ApplicationSubmittedToHqNotification $mail) use ($hqActive, $admin, $contract): bool {
        return $mail->hasTo($hqActive->email)
            && $mail->contract->is($contract)
            && $mail->submittedBy->is($admin)
            && ! str_contains($mail->render(), 'No. Fail Rujukan');
    });

    Mail::assertSent(ApplicationSubmittedToAdminNegeriNotification::class, function (ApplicationSubmittedToAdminNegeriNotification $mail) use ($admin, $contract): bool {
        return $mail->hasTo($admin->email)
            && $mail->contract->is($contract)
            && $mail->submittedBy->is($admin)
            && ! str_contains($mail->render(), 'No. Fail Rujukan');
    });

    Mail::assertNotSent(ApplicationSubmittedToHqNotification::class, function (ApplicationSubmittedToHqNotification $mail): bool {
        return $mail->hasTo('hq-inactive@example.test') || $mail->hasTo('negeri@example.test');
    });

    Mail::assertNotSent(ApplicationSubmittedToAdminNegeriNotification::class, function (ApplicationSubmittedToAdminNegeriNotification $mail): bool {
        return $mail->hasTo('negeri@example.test');
    });
});

test('submitting application to hq sends email to configured test recipient when set', function () {
    Mail::fake();
    config(['mail.test_recipient' => 'muhammad.hafiz@aadk.gov.my']);

    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    User::factory()->create(['role' => 'admin_hq', 'is_active' => true, 'email' => 'hq-active@example.test']);
    $contract = createStatusListContract('Johor');

    foreach (\App\Support\AdminProceedSteps::activeStepsFor($contract) as $step) {
        $key = \App\Support\AdminProceedSteps::keyForStep($step);
        $stepData = [
            'completed' => '1',
            'confirmed_accurate' => '1',
            'confirmed_promis' => '1',
            'notes' => 'Catatan '.$step,
            'no_rujukan' => 'AADK/REF/'.$step,
            'tarikh_surat' => '2026-01-15',
        ];

        if ($key === \App\Support\AdminProceedSteps::STEP_SURAT_AGENSI) {
            $stepData['agencies'] = collect(\App\Support\AdminProceedSteps::agencyKeys())
                ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '1'])
                ->all();
            $stepData['agency_dates'] = collect(\App\Support\AdminProceedSteps::agenciesRequiringDate())
                ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '2026-01-15'])
                ->all();
        }

        $this->actingAs($admin)->put(route('admin-proceed.update', $contract), [
            'current_step' => $step,
            'proceed_steps' => [$key => $stepData],
        ]);
        $contract->refresh();
    }

    $this->actingAs($admin)
        ->post(route('status-permohonan.submit-hq', $contract))
        ->assertRedirect(route('status-permohonan.index'));

    Mail::assertSent(ApplicationSubmittedToHqNotification::class, function (ApplicationSubmittedToHqNotification $mail): bool {
        return $mail->hasTo('muhammad.hafiz@aadk.gov.my');
    });

    Mail::assertSent(ApplicationSubmittedToAdminNegeriNotification::class, function (ApplicationSubmittedToAdminNegeriNotification $mail): bool {
        return $mail->hasTo('muhammad.hafiz@aadk.gov.my');
    });

    Mail::assertSent(ApplicationSubmittedToHqNotification::class, 1);
    Mail::assertSent(ApplicationSubmittedToAdminNegeriNotification::class, 1);
});

test('admin negeri can edit pending proceed application from status list', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor');

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertSuccessful()
        ->assertSee('Kemaskini Borang Permohonan Sewaan')
        ->assertSee('id="proceed-stepper"', false);

    $originalKategori = $contract->kategori_permohonan;

    $this->actingAs($admin)
        ->put(route('application.update', $contract), [
            'negeri' => 'Johor',
            'nama_ptj' => 'Premis Dikemaskini',
            'alamat_penuh' => 'Alamat dikemaskini',
            'nama_pemilik' => 'Pemilik Dikemaskini',
            'jenis_bangunan' => 'kompleks kerajaan',
            'kadar_sewa' => '1500.00',
            'keluasan_mp' => '180.00',
            'sah_sehingga' => now()->addMonths(6)->format('Y-m-d'),
            'step' => \App\Support\AdminProceedSteps::firstIncompleteStep($contract),
        ])
        ->assertRedirect(route('application.edit', $contract))
        ->assertSessionHas('success');

    $contract->refresh()->load('premise');

    expect($contract->kategori_permohonan)->toBe($originalKategori)
        ->and($contract->premise?->nama_ptj)->toBe('Premis Dikemaskini')
        ->and((float) $contract->keluasan_mp)->toBe(180.0)
        ->and($contract->submitted_by_user_id)->toBeNull();
});

test('admin hq list shows semak only for semakan puu application', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $contract->update(['semakan_count' => 1]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Semakan 1')
        ->assertSee(route('status-permohonan.review', $contract, false))
        ->assertDontSee(route('status-permohonan.approve-puu', $contract, false))
        ->assertDontSee(route('status-permohonan.reject-puu', $contract, false));
});

test('admin hq sees luluskan and batalkan actions on semakan puu review page', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $contract->update(['semakan_count' => 1]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Dalam tindakan PUU')
        ->assertSee(route('status-permohonan.approve-puu', $contract, false))
        ->assertSee(route('status-permohonan.reject-puu', $contract, false))
        ->assertSee('Luluskan')
        ->assertSee('Semak Semula');
});

test('admin negeri upload draft requires pdf file', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->from(route('status-permohonan.review', $contract))
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('not-a-pdf.txt', 100, 'text/plain'),
        ])
        ->assertRedirect(route('status-permohonan.review', $contract))
        ->assertSessionHasErrors('document');
});

test('admin negeri upload draft pdf moves application to semakan 1', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    $contract->update(['admin_negeri_user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('draf-perjanjian.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('status-permohonan.review', $contract))
        ->assertSessionHas('success');

    $contract->refresh();

    expect($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_SEMAKAN_PUU)
        ->and($contract->semakan_count)->toBe(1)
        ->and($contract->applicationStatusLabel())->toBe('Semakan 1')
        ->and($contract->documents()->count())->toBe(1)
        ->and($contract->documents()->first()?->semakan_round)->toBe(1);
});

test('draft agreement pdf opens via authenticated download route not public storage url', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $path = 'contract-documents/'.$contract->id.'/draf.pdf';
    Storage::disk('public')->put($path, '%PDF-1.4 test');
    $document = ContractDocument::query()->create([
        'contract_id' => $contract->id,
        'nama_fail' => 'draf.pdf',
        'path' => $path,
        'jenis' => ContractDocument::JENIS_DRAF_PERJANJIAN,
        'semakan_round' => 1,
        'user_id' => $admin->id,
    ]);

    expect($document->downloadUrl())->toBe('/kontrak/'.$contract->id.'/dokumen/'.$document->id);

    $this->actingAs($admin)
        ->get($document->downloadUrl())
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

test('approve and reject puu review label semakan documents as diluluskan or dibatalkan', function () {
    Storage::fake('public');
    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($negeri)
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('semakan-1.pdf', 100, 'application/pdf'),
        ]);

    $firstDocument = $contract->fresh()->documents()->first();
    expect($firstDocument?->semakan_status)->toBe(ContractDocument::SEMAKAN_MENUNGGU);

    $this->actingAs($hq)
        ->post(route('status-permohonan.reject-puu', $contract->fresh()));

    expect($firstDocument->fresh()->semakan_status)->toBe(ContractDocument::SEMAKAN_DIBATALKAN)
        ->and($firstDocument->fresh()->semakanStatusLabel())->toBe('Pindaan berdasarkan ulasan PUU');

    $this->actingAs($negeri)
        ->post(route('status-permohonan.upload-draft', $contract->fresh()), [
            'document' => UploadedFile::fake()->create('semakan-2.pdf', 100, 'application/pdf'),
        ]);

    $secondDocument = $contract->fresh()->documents()->where('semakan_round', 2)->first();

    $this->actingAs($hq)
        ->post(route('status-permohonan.approve-puu', $contract->fresh()));

    expect($secondDocument?->fresh()->semakan_status)->toBe(ContractDocument::SEMAKAN_DILULUSKAN)
        ->and($secondDocument?->fresh()->semakanStatusLabel())->toBe('Diluluskan')
        ->and($firstDocument->fresh()->semakanStatusLabel())->toBe('Pindaan berdasarkan ulasan PUU');
});

test('admin negeri review page shows semakan status labels in draft history', function () {
    Storage::fake('public');
    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    $contract->update(['admin_negeri_user_id' => $negeri->id]);

    $this->actingAs($negeri)
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('semakan-1.pdf', 100, 'application/pdf'),
        ]);

    $this->actingAs($hq)
        ->post(route('status-permohonan.reject-puu', $contract->fresh()));

    $this->actingAs($negeri)
        ->get(route('status-permohonan.review', $contract->fresh()))
        ->assertSuccessful()
        ->assertSee('Semakan 1')
        ->assertSee('Pindaan berdasarkan ulasan PUU')
        ->assertSee('Sejarah Muat Naik Draf');

    $this->actingAs($negeri)
        ->post(route('status-permohonan.upload-draft', $contract->fresh()), [
            'document' => UploadedFile::fake()->create('semakan-2.pdf', 100, 'application/pdf'),
        ]);

    $this->actingAs($negeri)
        ->get(route('status-permohonan.review', $contract->fresh()))
        ->assertSuccessful()
        ->assertSee('Semakan 2')
        ->assertSee('Menunggu Semakan PUU');

    $this->actingAs($hq)
        ->post(route('status-permohonan.approve-puu', $contract->fresh()));

    $this->actingAs($negeri)
        ->get(route('status-permohonan.review', $contract->fresh()))
        ->assertSuccessful()
        ->assertSee('Semakan 2')
        ->assertSee('Diluluskan')
        ->assertSee('Pindaan berdasarkan ulasan PUU');
});

test('admin negeri re-upload after puu rejection increments semakan count and keeps history', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    $contract->update(['admin_negeri_user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('draf-semakan-1.pdf', 100, 'application/pdf'),
        ]);

    $this->actingAs($hq)
        ->post(route('status-permohonan.reject-puu', $contract->fresh()));

    $this->actingAs($admin)
        ->post(route('status-permohonan.upload-draft', $contract->fresh()), [
            'document' => UploadedFile::fake()->create('draf-semakan-2.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('status-permohonan.review', $contract));

    $contract->refresh();

    expect($contract->semakan_count)->toBe(2)
        ->and($contract->applicationStatusLabel())->toBe('Semakan 2')
        ->and($contract->documents()->count())->toBe(2)
        ->and($contract->documents()->orderBy('semakan_round')->pluck('semakan_round')->all())->toBe([1, 2]);
});

test('admin hq approve puu review moves application to draf lulus with selesai status', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $contract->update(['semakan_count' => 1]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.approve-puu', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS)
        ->and($contract->fresh()->applicationStatusLabel())->toBe('Dokumen Perjanjian dikembalikan ke Cawangan Pembangunan AADK')
        ->and($contract->fresh()->draftAgreementCurrentStepIndex())->toBe(5);
});

test('admin hq reject puu review returns application to penyediaan draf with next semakan label', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $contract->update(['semakan_count' => 1]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.reject-puu', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN)
        ->and($contract->fresh()->semakan_count)->toBe(1)
        ->and($contract->fresh()->applicationStatusLabel())->toBe('Draf perjanjian dibatalkan PUU')
        ->and($contract->fresh()->draftAgreementCurrentStepIndex())->toBe(2);
});

test('admin negeri only sees semak action for draft agreement application', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Admin Johor Draf']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    $contract->update(['admin_negeri_user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Penyediaan Draf Perjanjian')
        ->assertSee(route('status-permohonan.review', $contract, false))
        ->assertDontSee(route('status-permohonan.approve-puu', $contract, false))
        ->assertDontSee(route('status-permohonan.reject-puu', $contract, false))
        ->assertDontSee('data-confirm-title="Padam Permohonan"', false);
});

test('admin negeri cannot upload draft when not in penyediaan draf stage', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_SEMAKAN_PUU);

    $this->actingAs($admin)
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('draf.pdf', 100, 'application/pdf'),
        ])
        ->assertForbidden();

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_SEMAKAN_PUU);
});

test('admin hq cannot approve puu when not in semakan puu stage', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->post(route('status-permohonan.approve-puu', $contract))
        ->assertForbidden();
});

test('admin hq selesai moves pindaan stage to draf perjanjian lulus status', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_PINDAAN_BERDASARKAN_PUU);
    $contract->update(['semakan_count' => 3]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.complete', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS)
        ->and($contract->fresh()->applicationStatusLabel())->toBe('Dokumen Perjanjian dikembalikan ke Cawangan Pembangunan AADK');
});

test('admin cannot selesai when application is not awaiting hq pindaan completion', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->post(route('status-permohonan.complete', $contract))
        ->assertForbidden();

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);
});

test('admin negeri sees acknowledgement checkbox on draf perjanjian lulus review page', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);
    $contract->update(['admin_negeri_user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Draf akhir diterima untuk penyediaan dokumen perjanjian')
        ->assertSee('3 Salinan Dokumen Perjanjian telah disediakan')
        ->assertSee('Dokumen Perjanjian telah ditandatangani oleh pemilik premis')
        ->assertSee('Dokumen Asal telah dihantar melalui Kurier / Serahan tangan kepada Cawangan Pembangunan')
        ->assertSee('Hantar Semula ke Ibu Pejabat')
        ->assertSee('Simpan')
        ->assertSee(route('status-permohonan.negeri-acknowledgements-autosave', $contract, false))
        ->assertSee(route('status-permohonan.return-hq', $contract, false));
});

test('admin negeri autosave persists partial draft acknowledgements', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);
    $contract->update([
        'negeri_draft_acknowledgements' => [
            'draf_akhir_diterima_acknowledged' => true,
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('checked', false);

    $this->actingAs($admin)
        ->patchJson(route('status-permohonan.negeri-acknowledgements-autosave', $contract), [
            'acknowledgements' => [
                'draf_akhir_diterima_acknowledged' => '1',
                'dokumen_perjanjian_disediakan_acknowledged' => '1',
                'dokumen_perjanjian_ditandatangani_acknowledged' => '0',
                'dokumen_asal_dihantar_acknowledged' => '0',
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Data disimpan');

    $saved = $contract->fresh()->negeri_draft_acknowledgements;

    expect($saved['draf_akhir_diterima_acknowledged'])->toBeTrue()
        ->and($saved['dokumen_perjanjian_disediakan_acknowledged'])->toBeTrue()
        ->and($saved['dokumen_perjanjian_ditandatangani_acknowledged'])->toBeFalse()
        ->and($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);
});

function negeriReturnDraftAcknowledgements(): array
{
    return [
        'draf_akhir_diterima_acknowledged' => '1',
        'dokumen_perjanjian_disediakan_acknowledged' => '1',
        'dokumen_perjanjian_ditandatangani_acknowledged' => '1',
        'dokumen_asal_dihantar_acknowledged' => '1',
    ];
}

test('admin negeri returns draft to hq after ticking acknowledgement checkbox', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->post(route('status-permohonan.return-hq', $contract), negeriReturnDraftAcknowledgements())
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ)
        ->and($contract->fresh()->negeri_draft_acknowledgements['draf_akhir_diterima_acknowledged'])->toBeTrue()
        ->and($contract->fresh()->negeri_draft_acknowledgements['dokumen_perjanjian_disediakan_acknowledged'])->toBeTrue()
        ->and($contract->fresh()->negeri_draft_acknowledgements['dokumen_perjanjian_ditandatangani_acknowledged'])->toBeTrue()
        ->and($contract->fresh()->negeri_draft_acknowledgements['dokumen_asal_dihantar_acknowledged'])->toBeTrue();
});

test('completed negeri acknowledgements stay visible to negeri and ibu pejabat', function () {
    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
    $contract->update([
        'negeri_draft_acknowledgements' => [
            'draf_akhir_diterima_acknowledged' => true,
            'dokumen_perjanjian_disediakan_acknowledged' => true,
            'dokumen_perjanjian_ditandatangani_acknowledged' => true,
            'dokumen_asal_dihantar_acknowledged' => true,
        ],
    ]);

    foreach ([$negeri, $hq] as $user) {
        $this->actingAs($user)
            ->get(route('status-permohonan.review', $contract))
            ->assertSuccessful()
            ->assertSee('Draf perjanjian telah diluluskan dan tindakan Negeri telah selesai.')
            ->assertSee('Draf akhir diterima untuk penyediaan dokumen perjanjian')
            ->assertSee('3 Salinan Dokumen Perjanjian telah disediakan')
            ->assertSee('Dokumen Perjanjian telah ditandatangani oleh pemilik premis')
            ->assertSee('Dokumen Asal telah dihantar melalui Kurier / Serahan tangan kepada Cawangan Pembangunan')
            ->assertSee('disabled', false)
            ->assertDontSee('Hantar Semula ke Ibu Pejabat');
    }
});

test('admin negeri must tick acknowledgement checkbox before returning draft', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->from(route('status-permohonan.review', $contract))
        ->post(route('status-permohonan.return-hq', $contract), [])
        ->assertRedirect(route('status-permohonan.review', $contract))
        ->assertSessionHasErrors([
            'draf_akhir_diterima_acknowledged',
            'dokumen_perjanjian_disediakan_acknowledged',
            'dokumen_perjanjian_ditandatangani_acknowledged',
            'dokumen_asal_dihantar_acknowledged',
        ]);

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);
});

test('admin negeri cannot return draft from another negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Selangor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->post(route('status-permohonan.return-hq', $contract), negeriReturnDraftAcknowledgements())
        ->assertForbidden();
});

test('admin negeri cannot return draft when not in draf perjanjian lulus stage', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->post(route('status-permohonan.return-hq', $contract), negeriReturnDraftAcknowledgements())
        ->assertForbidden();
});

test('review page shows progress stepper reflecting current stage', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Kemajuan Permohonan')
        ->assertSee('Permohonan Baru')
        ->assertSee('Semakan Ibu Pejabat')
        ->assertSee('Penyediaan Draf')
        ->assertSee('Dalam tindakan PUU')
        ->assertSee('Pindaan Berdasarkan PUU')
        ->assertSee('Pengesahan & Tandatangan')
        ->assertSee('Mati Setem')
        ->assertSee('Selesai');

    expect($contract->draftAgreementCurrentStepIndex())->toBe(6);

    $steps = $contract->draftAgreementProgressSteps();
    expect($steps[0]['state'])->toBe('completed')
        ->and($steps[6]['state'])->toBe('current')
        ->and($steps[7]['state'])->toBe('upcoming');
});

test('review page stepper shows dalam tindakan puu at semakan stage', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $contract->update(['semakan_count' => 1]);

    expect($contract->draftAgreementCurrentStepIndex())->toBe(3);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Dalam tindakan PUU')
        ->assertSee('Semakan 1');
});

test('status list kemajuan uses overall lifecycle progress not proceed-only percent', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $pendingProceed = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI);
    $pendingHq = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $draftPrep = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    $returned = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
    $completed = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI);

    expect($pendingProceed->overallProgressPercent())->toBe(0)
        ->and($pendingHq->overallProgressPercent())->toBe(13)
        ->and($draftPrep->overallProgressPercent())->toBe(25)
        ->and($returned->overallProgressPercent())->toBe(75)
        ->and($completed->overallProgressPercent())->toBe(100)
        ->and($pendingHq->adminListProgressWidthPercent())->toBe(13);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('13%')
        ->assertSee('25%')
        ->assertSee('75%')
        ->assertSee('100%');
});

test('admin hq sees three finalize checkboxes on returned draft review page', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);

    $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('Cawangan Pembangunan AADK menerima dokumen perjanjian')
        ->assertSee('Perjanjian ditandatangani TKPP AADK dikembalikan kepada AADK Negeri')
        ->assertSee('1 salinan perjanjian dihantar ke Cawangan Pembangunan AADK')
        ->assertSee('Hantar ke Negeri')
        ->assertSee('Mati Setem')
        ->assertSee('Simpan')
        ->assertSee(route('status-permohonan.hq-acknowledgements-autosave', $contract, false))
        ->assertSee(route('status-permohonan.finalize', $contract, false));
});

test('admin hq autosave persists partial draft acknowledgements', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);

    $this->actingAs($admin)
        ->patchJson(route('status-permohonan.hq-acknowledgements-autosave', $contract), [
            'acknowledgements' => [
                'terima_dokumen_acknowledged' => '1',
                'perjanjian_ditandatangani_acknowledged' => '0',
                'salinan_promis_acknowledged' => '0',
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Data disimpan');

    $saved = $contract->fresh()->hq_draft_acknowledgements;

    expect($saved['terima_dokumen_acknowledged'])->toBeTrue()
        ->and($saved['perjanjian_ditandatangani_acknowledged'])->toBeFalse()
        ->and($saved['salinan_promis_acknowledged'])->toBeFalse()
        ->and($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
});

test('admin hq finalizes application into kontrak sewaan after ticking all three checkboxes', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka', 'name' => 'Admin Melaka Selesai']);
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
    $contract->update(['admin_negeri_user_id' => $negeriAdmin->id, 'hq_approved_at' => now()]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.finalize', $contract), [
            'terima_dokumen_acknowledged' => '1',
            'perjanjian_ditandatangani_acknowledged' => '1',
            'salinan_promis_acknowledged' => '1',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_MATI_SETEM)
        ->and($contract->fresh()->isHqApproved())->toBeFalse()
        ->and($contract->fresh()->hq_draft_acknowledgements['terima_dokumen_acknowledged'])->toBeTrue()
        ->and($contract->fresh()->hq_draft_acknowledgements['perjanjian_ditandatangani_acknowledged'])->toBeTrue()
        ->and($contract->fresh()->hq_draft_acknowledgements['salinan_promis_acknowledged'])->toBeTrue();
});

test('admin negeri completes mati setem and moves application into kontrak sewaan', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka', 'name' => 'Admin Melaka Selesai']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_MATI_SETEM);
    $contract->update(['admin_negeri_user_id' => $negeriAdmin->id]);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.complete-mati-setem', $contract), [
            'mati_setem_acknowledged' => '1',
        ])
        ->assertRedirect(route('kontrak-sewaan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI)
        ->and($contract->fresh()->isHqApproved())->toBeTrue()
        ->and($contract->fresh()->status_aktif)->toBe('aktif')
        ->and($contract->fresh()->negeri_mati_setem_acknowledgements['mati_setem_acknowledged'])->toBeTrue();

    $this->actingAs($negeriAdmin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('Admin Melaka Selesai');
});

test('admin hq must tick all three checkboxes before finalizing', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);

    $this->actingAs($admin)
        ->from(route('status-permohonan.review', $contract))
        ->post(route('status-permohonan.finalize', $contract), [
            'terima_dokumen_acknowledged' => '1',
            'perjanjian_ditandatangani_acknowledged' => '1',
        ])
        ->assertRedirect(route('status-permohonan.review', $contract))
        ->assertSessionHasErrors('salinan_promis_acknowledged');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
});

test('admin hq cannot finalize when not in returned draft stage', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->post(route('status-permohonan.finalize', $contract), [
            'terima_dokumen_acknowledged' => '1',
            'perjanjian_ditandatangani_acknowledged' => '1',
            'salinan_promis_acknowledged' => '1',
        ])
        ->assertForbidden();
});

test('admin negeri cannot finalize application', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.finalize', $contract), [
            'terima_dokumen_acknowledged' => '1',
            'perjanjian_ditandatangani_acknowledged' => '1',
            'salinan_promis_acknowledged' => '1',
        ])
        ->assertForbidden();
});

test('admin negeri cannot edit application after it has been sent to hq', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertForbidden();
});
