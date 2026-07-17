<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\AdminProceedSteps;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPendingProceedContract(string $negeri = 'Johor', string $kategori = ApplicationCategories::BARU): RentalContract
{
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Proceed',
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Alamat ujian',
        'jenis_bangunan' => 'kompleks kerajaan',
        'nama_pemilik' => 'Pemilik Ujian',
    ]);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
        'kategori_permohonan' => $kategori,
    ]);
}

function proceedStepSavePayload(RentalContract $contract, int $step, ?string $notes = 'Catatan ujian'): array
{
    $key = AdminProceedSteps::keyForStep($step);
    $stepData = [
        'completed' => '1',
        'confirmed_accurate' => '1',
        'confirmed_promis' => '1',
        'notes' => $notes,
    ];

    if ($key === AdminProceedSteps::STEP_SURAT_AGENSI) {
        $stepData['agencies'] = collect(AdminProceedSteps::agencyKeys())
            ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '1'])
            ->all();
        $stepData['agency_dates'] = collect(AdminProceedSteps::agenciesRequiringDate())
            ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '2026-01-15'])
            ->all();
    }

    return [
        'current_step' => $step,
        'proceed_steps' => [
            $key => $stepData,
        ],
    ];
}

test('admin negeri can view dedicated tindakan page', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    $this->actingAs($admin)
        ->get(route('admin-proceed.show', $contract))
        ->assertSuccessful()
        ->assertSee('Langkah Tindakan')
        ->assertSee('Surat niat kepada pemilik premis')
        ->assertSee('Catatan', false)
        ->assertDontSee('Notis kepada pemilik premis');
});

test('application edit page highlights required fields validation helpers', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertSuccessful()
        ->assertSee('ApplicationFormValidation', false)
        ->assertSee('glass-input-required-empty', false)
        ->assertSee('glass-input-required-error', false)
        ->assertSee('form-showing-required-errors', false);
});

test('admin negeri edit page shows combined form and tindakan stepper', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertSuccessful()
        ->assertSee('Kemaskini Borang Permohonan Sewaan')
        ->assertSee('id="proceed-stepper"', false)
        ->assertSee('Langkah Tindakan')
        ->assertSee('Catatan', false)
        ->assertSee('Seterusnya')
        ->assertDontSee('Simpan Borang')
        ->assertSee('id="submit-hq-wrap" class="hidden"', false)
        ->assertDontSee(route('admin-proceed.show', $contract, false));
});

test('hantar ke hq button appears on edit page only when proceed steps are complete', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    foreach (AdminProceedSteps::activeStepsFor($contract) as $step) {
        $this->actingAs($admin)
            ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, $step));
        $contract->refresh();
    }

    expect($contract->isReadyToSendToHq())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertSuccessful()
        ->assertSee('Hantar ke Admin')
        ->assertSee('Seterusnya')
        ->assertSee('Hantar Permohonan ke Admin', false)
        ->assertSee('id="submit-hq-form"', false)
        ->assertSee('data-confirm-form="submit-hq-form"', false)
        ->assertSee('id="submit-hq-wrap" class="contents"', false);
});

test('submitting to hq from edit page confirmation form sends application to hq', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    foreach (AdminProceedSteps::activeStepsFor($contract) as $step) {
        $this->actingAs($admin)
            ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, $step));
        $contract->refresh();
    }

    $this->actingAs($admin)
        ->post(route('status-permohonan.submit-hq', $contract))
        ->assertRedirect(route('status-permohonan.index'))
        ->assertSessionHas('success');

    expect($contract->fresh()->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ)
        ->and($contract->fresh()->isProceedComplete())->toBeTrue();
});

test('lanjutan category shows step 1 and skips step 2 in tindakan page', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor', ApplicationCategories::LANJUTAN);

    $this->actingAs($admin)
        ->get(route('admin-proceed.show', $contract))
        ->assertSuccessful()
        ->assertSee('Notis kepada pemilik premis')
        ->assertDontSee('Surat niat kepada pemilik premis');
});

test('surat agensi step shows agency checklist and requires all agencies before confirmation', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor', ApplicationCategories::BARU);

    foreach ([2, 3, 4] as $step) {
        $this->actingAs($admin)->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, $step));
        $contract->refresh();
    }

    $this->actingAs($admin)
        ->get(route('admin-proceed.show', ['contract' => $contract, 'step' => 5]))
        ->assertSuccessful()
        ->assertSee('Keluarkan surat kepada agensi berikut dengan dikepilkan Borang JRP', false)
        ->assertSee('Ketua Pegawai Keselamatan Kerajaan Malaysia', false)
        ->assertSee('Gambar Bangunan Terkini', false);

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), [
            'current_step' => 5,
            'proceed_steps' => [
                AdminProceedSteps::STEP_SURAT_AGENSI => [
                    'completed' => '1',
                    'confirmed_accurate' => '1',
                    'confirmed_promis' => '1',
                    'notes' => 'Catatan agensi',
                    'agencies' => [
                        'kpksm' => '1',
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('admin-proceed.show', ['contract' => $contract, 'step' => 5]))
        ->assertSessionHas('success');

    expect(AdminProceedSteps::isStepCompleted($contract->fresh(), 5))->toBeFalse();
});

test('surat agensi step requires dates for agency letters except pelan and gambar', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor', ApplicationCategories::BARU);

    foreach ([2, 3, 4] as $step) {
        $this->actingAs($admin)->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, $step));
        $contract->refresh();
    }

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), [
            'current_step' => 5,
            'proceed_steps' => [
                AdminProceedSteps::STEP_SURAT_AGENSI => [
                    'completed' => '1',
                    'confirmed_accurate' => '1',
                    'confirmed_promis' => '1',
                    'notes' => 'Tanpa tarikh',
                    'agencies' => collect(AdminProceedSteps::agencyKeys())
                        ->mapWithKeys(fn (string $agencyKey): array => [$agencyKey => '1'])
                        ->all(),
                ],
            ],
        ]);

    expect(AdminProceedSteps::isStepCompleted($contract->fresh(), 5))->toBeFalse();

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, 5, 'Dengan tarikh'))
        ->assertRedirect();

    $contract->refresh();

    expect(AdminProceedSteps::isStepCompleted($contract, 5))->toBeTrue()
        ->and($contract->admin_proceed_progress[AdminProceedSteps::STEP_SURAT_AGENSI]['agency_dates']['kpksm'] ?? null)->toBe('2026-01-15')
        ->and(array_key_exists('pelan_lantai', $contract->admin_proceed_progress[AdminProceedSteps::STEP_SURAT_AGENSI]['agency_dates'] ?? []))->toBeFalse()
        ->and(array_key_exists('gambar_bangunan', $contract->admin_proceed_progress[AdminProceedSteps::STEP_SURAT_AGENSI]['agency_dates'] ?? []))->toBeFalse();
});

test('admin negeri cannot access edit page for contract in other negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Selangor');

    $this->actingAs($admin)
        ->get(route('application.edit', $contract))
        ->assertForbidden();
});

test('admin negeri cannot access tindakan page after application is no longer pending proceed', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');
    $contract->update(['workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ]);

    $this->actingAs($admin)
        ->get(route('admin-proceed.show', $contract))
        ->assertForbidden();
});

test('admin negeri can complete tindakan steps one by one and notes are persisted', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor', ApplicationCategories::BARU);

    foreach (AdminProceedSteps::activeStepsFor($contract) as $index => $step) {
        $notes = 'Catatan langkah '.$step;

        $response = $this->actingAs($admin)
            ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, $step, $notes));

        if ($index === count(AdminProceedSteps::activeStepsFor($contract)) - 1) {
            $response->assertRedirect(route('status-permohonan.index'));
        } else {
            $nextStep = AdminProceedSteps::nextStep($contract, $step);
            $response->assertRedirect(route('admin-proceed.show', ['contract' => $contract, 'step' => $nextStep]));
        }

        $contract->refresh();
        $progress = AdminProceedSteps::progressFor($contract);
        $key = AdminProceedSteps::keyForStep($step);

        expect(AdminProceedSteps::isStepCompleted($contract, $step))->toBeTrue()
            ->and($progress[$key]['notes'])->toBe($notes);
    }

    expect(AdminProceedSteps::allCompleted($contract))->toBeTrue()
        ->and($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI)
        ->and($contract->isReadyToSendToHq())->toBeTrue()
        ->and($contract->workflowLabel())->toBe('Permohonan sedia untuk dihantar ke HQ');
});

test('autosave draft persists form text fields immediately', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    $this->actingAs($admin)
        ->patchJson(route('application.autosave', $contract), [
            'negeri' => 'Johor',
            'nama_ptj' => 'Premis Autosave Segera',
            'alamat_penuh' => 'Alamat autosave segera',
            'nama_pemilik' => 'Pemilik Autosave',
            'jenis_bangunan' => 'kompleks kerajaan',
            'kadar_sewa' => '1750.50',
            'keluasan_mp' => '210.25',
            'sah_sehingga' => now()->addMonths(8)->format('Y-m-d'),
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $contract->refresh()->load('premise');

    expect($contract->premise?->nama_ptj)->toBe('Premis Autosave Segera')
        ->and($contract->premise?->alamat_penuh)->toBe('Alamat autosave segera')
        ->and($contract->premise?->nama_pemilik)->toBe('Pemilik Autosave')
        ->and((float) $contract->kadar_sewa_bulanan)->toBe(1750.50)
        ->and((float) $contract->keluasan_mp)->toBe(210.25);
});

test('autosave draft does not mark tindakan step as completed from checkbox alone', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');
    $key = AdminProceedSteps::keyForStep(2);

    $this->actingAs($admin)
        ->patchJson(route('application.autosave', $contract), [
            'proceed_steps' => [
                $key => [
                    'completed' => '1',
                    'notes' => 'Catatan draf',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('steps.'.$key.'.completed', false)
        ->assertJsonPath('steps.'.$key.'.notes', 'Catatan draf');

    expect(AdminProceedSteps::isStepCompleted($contract->fresh(), 2))->toBeFalse();
});

test('autosave draft unmarks completed step when checkbox is unticked', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');
    $key = AdminProceedSteps::keyForStep(2);

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, 2));

    $contract->refresh();
    expect(AdminProceedSteps::isStepCompleted($contract, 2))->toBeTrue()
        ->and($contract->proceedProgressPercent())->toBeGreaterThan(0);

    $this->actingAs($admin)
        ->patchJson(route('application.autosave', $contract), [
            'proceed_steps' => [
                $key => [
                    'completed' => '0',
                    'confirmed_accurate' => '0',
                    'confirmed_promis' => '0',
                    'notes' => 'Catatan ujian',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('progress.progressPercent', 0)
        ->assertJsonPath('steps.'.$key.'.completed', false);

    $contract->refresh();
    expect(AdminProceedSteps::isStepCompleted($contract, 2))->toBeFalse()
        ->and($contract->proceedProgressPercent())->toBe(0);
});

test('autosave draft for one step does not reset other completed steps', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');
    $stepTwoKey = AdminProceedSteps::keyForStep(2);
    $stepFourKey = AdminProceedSteps::keyForStep(4);

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, 2));

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, 4));

    $contract->refresh();
    expect(AdminProceedSteps::isStepCompleted($contract, 2))->toBeTrue()
        ->and(AdminProceedSteps::isStepCompleted($contract, 4))->toBeTrue();

    $this->actingAs($admin)
        ->patchJson(route('application.autosave', $contract), [
            'proceed_steps' => [
                $stepTwoKey => [
                    'completed' => '0',
                    'confirmed_accurate' => '0',
                    'confirmed_promis' => '0',
                    'notes' => 'Catatan dikemaskini',
                ],
            ],
        ])
        ->assertSuccessful();

    $contract->refresh();

    expect(AdminProceedSteps::isStepCompleted($contract, 2))->toBeFalse()
        ->and(AdminProceedSteps::isStepCompleted($contract, 4))->toBeTrue()
        ->and($contract->proceedProgressPercent())->toBe(25);
});

test('autosave draft for one step does not reset marked_complete on other steps', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');
    $stepTwoKey = AdminProceedSteps::keyForStep(2);
    $stepFourKey = AdminProceedSteps::keyForStep(4);

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, 4));

    $contract->refresh();
    $progress = AdminProceedSteps::progressFor($contract);

    expect($progress[$stepFourKey]['marked_complete'] ?? false)->toBeTrue();

    $this->actingAs($admin)
        ->patchJson(route('application.autosave', $contract), [
            'proceed_steps' => [
                $stepTwoKey => [
                    'completed' => '0',
                    'confirmed_accurate' => '0',
                    'confirmed_promis' => '0',
                    'notes' => 'Catatan dikemaskini',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('steps.'.$stepFourKey.'.marked_complete', true)
        ->assertJsonPath('steps.'.$stepFourKey.'.completed', true);

    $contract->refresh();
    $progress = AdminProceedSteps::progressFor($contract);

    expect($progress[$stepFourKey]['marked_complete'] ?? false)->toBeTrue()
        ->and($progress[$stepFourKey]['completed'] ?? false)->toBeTrue();
});

test('admin negeri can navigate to completed tindakan step via stepper', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createPendingProceedContract('Johor');

    $this->actingAs($admin)
        ->put(route('admin-proceed.update', $contract), proceedStepSavePayload($contract, 2));

    $this->actingAs($admin)
        ->get(route('admin-proceed.show', ['contract' => $contract, 'step' => 2]))
        ->assertSuccessful()
        ->assertSee('data-proceed-step="2"', false)
        ->assertSee('id="proceed-step-panel-2"', false)
        ->assertSee('Catatan ujian', false);
});

test('status permohonan shows hantar ke hq only when all tindakan steps are complete', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $pendingProceed = createPendingProceedContract('Johor');
    $readyToSend = createPendingProceedContract('Johor');

    foreach (AdminProceedSteps::activeStepsFor($readyToSend) as $step) {
        $this->actingAs($admin)
            ->put(route('admin-proceed.update', $readyToSend), proceedStepSavePayload($readyToSend, $step));
        $readyToSend->refresh();
    }

    $sentPremise = Premise::query()->create([
        'nama_ptj' => 'Premis Dihantar',
        'negeri' => 'Johor',
        'daerah' => 'Johor',
        'alamat_penuh' => 'Alamat',
    ]);

    $sentToHq = RentalContract::query()->create([
        'premise_id' => $sentPremise->id,
        'submitted_by_user_id' => null,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
    ]);

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee(route('application.edit', $pendingProceed, false))
        ->assertSee('Hantar ke Admin', false)
        ->assertSee(route('status-permohonan.submit-hq', $readyToSend, false))
        ->assertDontSee(route('status-permohonan.submit-hq', $pendingProceed, false))
        ->assertDontSee(route('application.edit', $sentToHq, false));
});
