<?php

use App\Mail\ApplicationWorkflowStageNotification;
use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createWorkflowMailContract(User $negeriAdmin, string $workflow): RentalContract
{
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Emel Stepper',
        'negeri' => $negeriAdmin->negeri,
        'daerah' => $negeriAdmin->negeri,
        'alamat_penuh' => 'Alamat',
        'nama_pemilik' => 'Pemilik',
    ]);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'admin_negeri_user_id' => $negeriAdmin->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => $workflow,
        'kategori_permohonan' => ApplicationCategories::BARU,
        'semakan_count' => 1,
    ]);
}

function assertStageMailSentToHqAndNegeri(User $hq, User $negeri, string $subjectContains): void
{
    Mail::assertSent(ApplicationWorkflowStageNotification::class, function (ApplicationWorkflowStageNotification $mail) use ($hq, $subjectContains): bool {
        return $mail->hasTo($hq->email)
            && str_contains($mail->envelope()->subject, $subjectContains);
    });

    Mail::assertSent(ApplicationWorkflowStageNotification::class, function (ApplicationWorkflowStageNotification $mail) use ($negeri, $subjectContains): bool {
        return $mail->hasTo($negeri->email)
            && str_contains($mail->envelope()->subject, $subjectContains);
    });
}

test('uploading draft agreement emails hq and negeri for puu stage', function () {
    Mail::fake();
    Storage::fake('public');

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);

    $this->actingAs($negeri)
        ->post(route('status-permohonan.upload-draft', $contract), [
            'document' => UploadedFile::fake()->create('draf.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Semakan PUU');
});

test('rejecting puu review emails hq and negeri for pindaan stage', function () {
    Mail::fake();

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_SEMAKAN_PUU);

    $this->actingAs($hq)
        ->post(route('status-permohonan.reject-puu', $contract))
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Pindaan');
});

test('approving puu review emails hq and negeri for draf lulus stage', function () {
    Mail::fake();

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_SEMAKAN_PUU);

    $this->actingAs($hq)
        ->post(route('status-permohonan.approve-puu', $contract))
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Draf Perjanjian Diluluskan');
});

test('completing pindaan emails hq and negeri for draf lulus stage', function () {
    Mail::fake();

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_PINDAAN_BERDASARKAN_PUU);

    $this->actingAs($hq)
        ->post(route('status-permohonan.complete', $contract))
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Draf Perjanjian Diluluskan');
});

test('returning draft to hq emails both roles for pengesahan stage', function () {
    Mail::fake();

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS);

    $this->actingAs($negeri)
        ->post(route('status-permohonan.return-hq', $contract), [
            'draf_akhir_diterima_acknowledged' => '1',
            'dokumen_perjanjian_disediakan_acknowledged' => '1',
            'dokumen_perjanjian_ditandatangani_acknowledged' => '1',
            'dokumen_asal_dihantar_acknowledged' => '1',
        ])
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Pengesahan & Tandatangan');
});

test('hq finalize emails both roles for mati setem stage', function () {
    Mail::fake();

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ);

    $this->actingAs($hq)
        ->post(route('status-permohonan.finalize', $contract), [
            'terima_dokumen_acknowledged' => '1',
            'perjanjian_ditandatangani_acknowledged' => '1',
            'salinan_promis_acknowledged' => '1',
        ])
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Mati Setem');
});

test('completing mati setem emails both roles for selesai stage', function () {
    Mail::fake();

    $negeri = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Melaka']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $contract = createWorkflowMailContract($negeri, RentalContract::WORKFLOW_MATI_SETEM);

    $this->actingAs($negeri)
        ->post(route('status-permohonan.complete-mati-setem', $contract), [
            'mati_setem_lhdn_acknowledged' => '1',
            'edaran_pemilik_acknowledged' => '1',
        ])
        ->assertRedirect();

    assertStageMailSentToHqAndNegeri($hq, $negeri, 'Selesai');
});
