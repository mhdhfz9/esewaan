<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use App\Support\HqJrpChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createHqReviewContractForAutosave(string $negeri = 'Melaka'): RentalContract
{
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Checklist Autosave',
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Alamat',
        'nama_pemilik' => 'Pemilik',
    ]);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
        'kategori_permohonan' => ApplicationCategories::BARU,
    ]);
}

test('admin hq can autosave jrp checklist without approving application', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createHqReviewContractForAutosave();

    $this->actingAs($admin)
        ->patch(route('status-permohonan.checklist-autosave', $contract), [
            'jrp_checklist' => [
                HqJrpChecklist::KP => '1',
                HqJrpChecklist::MOF => '0',
                HqJrpChecklist::EPU => '0',
                HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '1',
                HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => '0',
                HqJrpChecklist::AADK_SUBMIT_BPH => '0',
                HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => '0',
                'dates' => [
                    HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '2026-07-16',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Checklist disimpan.',
        ]);

    $contract->refresh();

    expect($contract->isPendingHqReview())->toBeTrue()
        ->and($contract->hq_approved_at)->toBeNull()
        ->and($contract->hq_jrp_checklist[HqJrpChecklist::KP])->toBeTrue()
        ->and($contract->hq_jrp_checklist[HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS])->toBeTrue()
        ->and($contract->hq_jrp_checklist['dates'][HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS])->toBe('2026-07-16');
});

test('hq review page restores autosaved jrp checklist values', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $contract = createHqReviewContractForAutosave();
    $contract->update([
        'hq_jrp_checklist' => [
            HqJrpChecklist::KP => true,
            HqJrpChecklist::MOF => false,
            HqJrpChecklist::EPU => false,
            HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => true,
            HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => false,
            HqJrpChecklist::AADK_SUBMIT_BPH => false,
            HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => false,
            'dates' => [
                HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '2026-07-16',
            ],
        ],
    ]);

    $html = $this->actingAs($admin)
        ->get(route('status-permohonan.review', $contract))
        ->assertSuccessful()
        ->assertSee('id="hq-jrp-checklist"', false)
        ->assertSee('checklist-autosave', false)
        ->getContent();

    preg_match('/<input\b[^>]*data-jrp-key="aadk_development_receive_epu_comments"[^>]*>/', $html, $checkbox);
    preg_match('/id="jrp_checklist_date_aadk_development_receive_epu_comments"[^>]*value="([^"]*)"/', $html, $date);

    expect($checkbox[0] ?? '')->toContain('checked')
        ->and($date[1] ?? '')->toBe('2026-07-16');
});

test('admin negeri cannot autosave hq jrp checklist', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka']);
    $contract = createHqReviewContractForAutosave('Melaka');

    $this->actingAs($admin)
        ->patch(route('status-permohonan.checklist-autosave', $contract), [
            'jrp_checklist' => [
                HqJrpChecklist::KP => '1',
            ],
        ])
        ->assertForbidden();
});
