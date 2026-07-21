<?php

use App\Mail\ApplicationApprovedByHqNotification;
use App\Mail\ApplicationSubmittedToAdminNegeriNotification;
use App\Mail\ApplicationSubmittedToHqNotification;
use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

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
    $contract->premise->update(['nama_ptj' => 'Premis Sejarah HQ']);

    $this->actingAs($negeriAdmin)
        ->delete(route('status-permohonan.destroy', $contract), [
            'delete_reason' => 'Maklumat premis tidak tepat dan perlu diisi semula.',
        ]);

    $this->actingAs($hqAdmin)
        ->get(route('status-permohonan.index', ['tab' => 'history']))
        ->assertSuccessful()
        ->assertSee('Sejarah')
        ->assertSee('Premis Sejarah HQ')
        ->assertSee('Maklumat premis tidak tepat dan perlu diisi semula.')
        ->assertSee('Admin Johor');
});

test('admin negeri cannot delete permohonan after sent to hq', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->delete(route('status-permohonan.destroy', $contract), [
            'delete_reason' => 'Cuba padam selepas dihantar ke HQ.',
        ])
        ->assertForbidden();

    expect(RentalContract::query()->count())->toBe(1);
});

test('admin negeri can request withdrawal for hq submitted permohonan', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan HQ.',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    $contract->refresh();

    expect($contract->withdrawal_status)->toBe(RentalContract::WITHDRAWAL_PENDING)
        ->and($contract->withdrawal_reason)->toBe('Maklumat premis perlu dikemaskini sebelum semakan HQ.')
        ->and($contract->withdrawal_requested_by_user_id)->toBe($admin->id)
        ->and($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
});

test('admin hq can approve withdrawal request and return permohonan to negeri', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hqAdmin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan HQ.',
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
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan HQ.',
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
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan HQ.',
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
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan HQ.',
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
        ->assertSee('Maklumat premis perlu dikemaskini sebelum semakan HQ.')
        ->assertSee('Luluskan Tarik Semula')
        ->assertSee('Tolak Tarik Semula')
        ->assertDontSee('Sahkan Permohonan');
});

test('admin negeri sees menunggu kelulusan tarik semula label for own withdrawal request', function () {
    $negeriAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($negeriAdmin)
        ->post(route('status-permohonan.request-withdrawal', $contract), [
            'withdrawal_reason' => 'Maklumat premis perlu dikemaskini sebelum semakan HQ.',
        ]);

    $this->actingAs($negeriAdmin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Menunggu kelulusan tarik semula')
        ->assertDontSee('Negeri memohon untuk tarik semula');
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
    $contract->premise->update(['nama_ptj' => 'Premis Semakan HQ']);
    $contract->update([
        'sah_sehingga' => now()->addMonths(3),
        'admin_proceed_progress' => [
            \App\Support\AdminProceedSteps::STEP_SURAT_NIAT => [
                'completed' => true,
                'confirmed_accurate' => true,
                'confirmed_promis' => true,
                'notes' => 'Surat niat telah dikeluarkan kepada pemilik.',
                'completed_at' => now()->toIso8601String(),
            ],
            \App\Support\AdminProceedSteps::STEP_SURAT_AGENSI => [
                'completed' => true,
                'confirmed_accurate' => true,
                'confirmed_promis' => true,
                'notes' => 'Surat agensi lengkap.',
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
        ->assertSee('Premis Semakan HQ')
        ->assertSee('Langkah Tindakan Pegawai Negeri')
        ->assertSee('Pengesahan HQ')
        ->assertSee('Surat niat telah dikeluarkan kepada pemilik.')
        ->assertSee('Surat agensi lengkap.')
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
        ->and($normalized['dates'])->not->toHaveKey(\App\Support\HqJrpChecklist::KP);
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

    expect($html)->not->toContain('data-jrp-date-for="kp"')
        ->and($html)->not->toContain('data-jrp-date-for="mof_negeri_budget_office"')
        ->and($html)->not->toContain('data-jrp-date-for="epu_jpm_planning"');
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
        ->get(route('status-permohonan.index', ['search' => 'Menunggu semakan HQ']))
        ->assertSuccessful()
        ->assertSee('Menunggu semakan HQ')
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
        ->assertSee('Menunggu semakan HQ');
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

test('admin hq sees semak, hantar ke puu and selesai actions for draft agreement application', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Penyediaan Draf Perjanjian')
        ->assertSee('Hantar ke PUU')
        ->assertSee('Selesai')
        ->assertSee(route('status-permohonan.send-puu', $contract, false));
});

test('admin hq hantar ke puu moves draft agreement to semakan 1 then increments', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->post(route('status-permohonan.send-puu', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_SEMAKAN_PUU)
        ->and($contract->fresh()->semakan_count)->toBe(1)
        ->and($contract->fresh()->applicationStatusLabel())->toBe('Semakan 1');

    $this->actingAs($admin)
        ->post(route('status-permohonan.send-puu', $contract))
        ->assertRedirect(route('status-permohonan.index'));

    expect($contract->fresh()->semakan_count)->toBe(2)
        ->and($contract->fresh()->applicationStatusLabel())->toBe('Semakan 2');
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
        ->assertDontSee('Hantar ke PUU')
        ->assertDontSee('Selesai')
        ->assertDontSee('data-confirm-title="Padam Permohonan"', false);
});

test('admin negeri cannot send draft agreement to puu', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->post(route('status-permohonan.send-puu', $contract))
        ->assertForbidden();

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
});

test('admin negeri cannot send to puu when not in draft agreement stage', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);

    $this->actingAs($admin)
        ->post(route('status-permohonan.send-puu', $contract))
        ->assertForbidden();
});

test('admin hq selesai moves draft agreement to negeri with draf perjanjian lulus status', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createStatusListContract('Melaka', RentalContract::WORKFLOW_SEMAKAN_PUU);
    $contract->update(['semakan_count' => 3]);

    $this->actingAs($admin)
        ->post(route('status-permohonan.complete', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS)
        ->and($contract->fresh()->applicationStatusLabel())->toBe('Draf Perjanjian Lulus Tanpa Pindaan');
});

test('admin cannot selesai when application is not awaiting hq draft action', function () {
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
        ->assertSee('Draf akhir dikembalikan kepada AADK Negeri')
        ->assertSee('Hantar Semula ke Admin')
        ->assertSee(route('status-permohonan.return-hq', $contract, false));
});

test('admin negeri returns draft to hq after ticking acknowledgement checkbox', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->post(route('status-permohonan.return-hq', $contract), [
            'draf_akhir_acknowledged' => '1',
        ])
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
});

test('admin negeri must tick acknowledgement checkbox before returning draft', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->from(route('status-permohonan.review', $contract))
        ->post(route('status-permohonan.return-hq', $contract), [])
        ->assertRedirect(route('status-permohonan.review', $contract))
        ->assertSessionHasErrors('draf_akhir_acknowledged');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);
});

test('admin negeri cannot return draft from another negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Selangor', RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($admin)
        ->post(route('status-permohonan.return-hq', $contract), [
            'draf_akhir_acknowledged' => '1',
        ])
        ->assertForbidden();
});

test('admin negeri cannot return draft when not in draf perjanjian lulus stage', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($admin)
        ->post(route('status-permohonan.return-hq', $contract), [
            'draf_akhir_acknowledged' => '1',
        ])
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
        ->assertSee('Semakan HQ')
        ->assertSee('Penyediaan Draf')
        ->assertSee('Pengesahan & Tandatangan')
        ->assertSee('Selesai');

    expect($contract->draftAgreementCurrentStepIndex())->toBe(4);

    $steps = $contract->draftAgreementProgressSteps();
    expect($steps[0]['state'])->toBe('completed')
        ->and($steps[4]['state'])->toBe('current')
        ->and($steps[5]['state'])->toBe('upcoming');
});

test('status list kemajuan uses overall lifecycle progress not proceed-only percent', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $pendingProceed = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI);
    $pendingHq = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ);
    $draftPrep = createStatusListContract('Johor', RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
    $returned = createStatusListContract('Johor', RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);
    $completed = createStatusListContract('Johor', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI);

    expect($pendingProceed->overallProgressPercent())->toBe(0)
        ->and($pendingHq->overallProgressPercent())->toBe(20)
        ->and($draftPrep->overallProgressPercent())->toBe(40)
        ->and($returned->overallProgressPercent())->toBe(80)
        ->and($completed->overallProgressPercent())->toBe(100)
        ->and($pendingHq->adminListProgressWidthPercent())->toBe(20);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('20%')
        ->assertSee('40%')
        ->assertSee('80%')
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
        ->assertSee('Selesai')
        ->assertSee(route('status-permohonan.finalize', $contract, false));
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

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI)
        ->and($contract->fresh()->isHqApproved())->toBeTrue();

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
