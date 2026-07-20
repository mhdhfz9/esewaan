<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\AdminProceedSteps;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array<string, array<string, mixed>>
 */
function completedLanjutanProceedProgress(): array
{
    $completedStep = [
        'completed' => true,
        'marked_complete' => true,
        'confirmed_accurate' => true,
        'confirmed_promis' => true,
    ];

    return [
        AdminProceedSteps::STEP_NOTIS_PEMILIK => $completedStep,
        AdminProceedSteps::STEP_BORANG_JRP => $completedStep,
        AdminProceedSteps::STEP_SURAT_JPPH => $completedStep,
        AdminProceedSteps::STEP_SURAT_AGENSI => array_merge($completedStep, [
            'agencies' => collect(AdminProceedSteps::agencyKeys())
                ->mapWithKeys(fn (string $key): array => [$key => true])
                ->all(),
            'agency_dates' => collect(AdminProceedSteps::agenciesRequiringDate())
                ->mapWithKeys(fn (string $key): array => [$key => '2026-01-15'])
                ->all(),
        ]),
    ];
}

function createApprovedParentContract(
    string $negeri = 'Johor',
    float $rent = 1000,
    ?User $submittedBy = null,
): RentalContract {
    $premise = Premise::query()->create([
        'nama_ptj' => 'AADK Daerah Muar',
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Tingkat 2, Plaza Lama',
        'jenis_bangunan' => \App\Support\BuildingTypes::values()[0] ?? 'kompleks_kerajaan',
        'nama_pemilik' => 'Pemilik Lama',
        'kadar_sewa' => $rent,
    ]);

    return RentalContract::query()->create([
        'premise_id' => $premise->id,
        'submitted_by_user_id' => $submittedBy?->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => $rent,
        'keluasan_mp' => 150.5,
        'status_aktif' => 'aktif',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'kategori_permohonan' => ApplicationCategories::BARU,
        'hq_approved_at' => now(),
        'sah_sehingga' => now()->addYear(),
    ]);
}

test('admin negeri can start a lanjutan from an existing contract', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor', 1200);

    $response = $this->actingAs($admin)
        ->post(route('kontrak-sewaan.follow-up', $parent), [
            'kategori_permohonan' => ApplicationCategories::LANJUTAN,
        ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->first();

    expect($child)->not->toBeNull()
        ->and($child->kategori_permohonan)->toBe(ApplicationCategories::LANJUTAN)
        ->and($child->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI)
        ->and($child->premise_id)->not->toBe($parent->premise_id)
        ->and($child->submitted_by_user_id)->toBeNull()
        ->and((float) $child->kadar_sewa_bulanan)->toBe(0.0)
        ->and($child->sah_sehingga)->toBeNull()
        ->and((float) $child->keluasan_mp)->toBe(150.5)
        ->and($child->premise->kadar_sewa)->toBeNull()
        ->and($child->premise->nama_ptj)->toBe('')
        ->and($child->premise->alamat_penuh)->toBe('')
        ->and($child->premise->jenis_bangunan)->toBeNull()
        ->and($child->premise->nama_pemilik)->toBeNull();

    $response->assertRedirect(route('application.edit', $child));

    $this->actingAs($admin)
        ->get(route('application.edit', $child))
        ->assertSuccessful()
        ->assertSee('Maklumat Baharu')
        ->assertSee('value="150.50"', false)
        ->assertSee('Dikunci mengikut kontrak sedia ada.')
        ->assertSee($parent->premise->nama_ptj);
});

test('admin negeri can start a pindah from an existing contract', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor', 1000);

    $this->actingAs($admin)
        ->post(route('kontrak-sewaan.follow-up', $parent), [
            'kategori_permohonan' => ApplicationCategories::PINDAH,
        ])
        ->assertSessionHas('success');

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    expect($child->kategori_permohonan)->toBe(ApplicationCategories::PINDAH)
        ->and($child->submitted_by_user_id)->toBeNull()
        ->and($child->premise->nama_ptj)->toBe('')
        ->and($child->premise->alamat_penuh)->toBe('')
        ->and($child->premise->jenis_bangunan)->toBeNull()
        ->and($child->premise->nama_pemilik)->toBeNull()
        ->and($child->premise->kadar_sewa)->toBeNull()
        ->and((float) $child->kadar_sewa_bulanan)->toBe(0.0)
        ->and($child->keluasan_mp)->toBeNull()
        ->and($child->sah_sehingga)->toBeNull();

    $this->actingAs($admin)
        ->get(route('application.edit', $child))
        ->assertSuccessful()
        ->assertSee('value=""', false)
        ->assertSee($parent->premise->nama_ptj);
});

test('follow-up cannot be started on a contract that is not hq approved', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');
    $parent->update(['workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI, 'hq_approved_at' => null]);

    $this->actingAs($admin)
        ->post(route('kontrak-sewaan.follow-up', $parent), [
            'kategori_permohonan' => ApplicationCategories::LANJUTAN,
        ])
        ->assertForbidden();

    expect(RentalContract::query()->where('parent_contract_id', $parent->id)->count())->toBe(0);
});

test('kontrak sewaan list shows pending follow-up category in status badge', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('Dalam Tindakan Lanjutan')
        ->assertDontSee('Dalam Tindakan Lanjutan/Pindah')
        ->assertDontSee('Susulan lanjutan dalam proses')
        ->assertDontSee('>Susulan dalam proses<', false);
});

test('kontrak sewaan list shows pindah follow-up category in status badge', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::PINDAH,
    ]);

    $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('Dalam Tindakan Pindah')
        ->assertDontSee('Dalam Tindakan Lanjutan/Pindah');
});

test('follow-up cannot be started twice for the same contract', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);

    $this->actingAs($admin)
        ->post(route('kontrak-sewaan.follow-up', $parent), [
            'kategori_permohonan' => ApplicationCategories::PINDAH,
        ])
        ->assertForbidden();

    expect(RentalContract::query()->where('parent_contract_id', $parent->id)->count())->toBe(1);
});

test('admin negeri cannot start follow-up for a contract outside their negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Kelantan']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)
        ->post(route('kontrak-sewaan.follow-up', $parent), [
            'kategori_permohonan' => ApplicationCategories::LANJUTAN,
        ])
        ->assertForbidden();
});

test('approving a follow-up supersedes the parent contract', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $hq = User::factory()->create(['role' => 'admin_hq']);
    $parent = createApprovedParentContract('Johor', 800);

    $child = RentalContract::query()->create([
        'parent_contract_id' => $parent->id,
        'premise_id' => $parent->premise_id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 900,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);

    $this->actingAs($hq)
        ->post(route('status-permohonan.approve', $child), hqJrpChecklistFor($child))
        ->assertRedirect(route('status-permohonan.index'));

    $parent->refresh();
    $child->refresh();

    expect($parent->isSuperseded())->toBeTrue()
        ->and($parent->superseded_by_contract_id)->toBe($child->id)
        ->and($parent->status_aktif)->toBe('tamat_tempoh')
        ->and($child->workflow_tahap)->toBe(RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN);
});

test('superseded contract is hidden from the kontrak sewaan list but the follow-up appears', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor', 800);
    $parent->premise->update(['nama_ptj' => 'Premis Lama Digantikan']);

    $child = RentalContract::query()->create([
        'parent_contract_id' => $parent->id,
        'premise_id' => Premise::query()->create([
            'nama_ptj' => 'Premis Baharu Lanjutan',
            'negeri' => 'Johor',
            'daerah' => 'Johor',
            'alamat_penuh' => 'Alamat baharu',
            'nama_pemilik' => 'Pemilik',
        ])->id,
        'submitted_by_user_id' => $parent->submitted_by_user_id,
        'admin_negeri_user_id' => $admin->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 900,
        'status_aktif' => 'aktif',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
        'hq_approved_at' => now(),
    ]);

    $parent->update(['superseded_at' => now(), 'superseded_by_contract_id' => $child->id]);

    $response = $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertSee('Lanjutan');

    $listedIds = $response->viewData('contracts')->pluck('id');

    expect($listedIds)->toContain($child->id)
        ->and($listedIds)->not->toContain($parent->id);
});

test('rent difference band flags below and above the RM500 threshold', function () {
    $parent = createApprovedParentContract('Johor', 1000);

    $below = new RentalContract(['kadar_sewa_bulanan' => 1400]);
    $below->setRelation('parentContract', $parent);
    expect($below->rentDifferenceBand())->toBe('below');

    $above = new RentalContract(['kadar_sewa_bulanan' => 1600]);
    $above->setRelation('parentContract', $parent);
    expect($above->rentDifferenceBand())->toBe('above')
        ->and($above->rentDifferenceFromParent())->toBe(600.0);
});

test('admin negeri can revert a mistaken follow-up back to the original contract', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor', 1000);

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::PINDAH,
    ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    expect(RentalContract::query()->count())->toBe(2)
        ->and(Premise::query()->count())->toBe(2);

    $this->actingAs($admin)
        ->delete(route('kontrak-sewaan.follow-up.cancel', $child))
        ->assertRedirect(route('kontrak-sewaan.index'))
        ->assertSessionHas('success');

    expect(RentalContract::withTrashed()->count())->toBe(1)
        ->and(Premise::query()->count())->toBe(1)
        ->and(RentalContract::query()->find($parent->id))->not->toBeNull()
        ->and($parent->fresh()->hasPendingFollowUp())->toBeFalse();
});

test('reverting a follow-up keeps the original contract available for a new follow-up', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);
    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    $this->actingAs($admin)->delete(route('kontrak-sewaan.follow-up.cancel', $child));

    $this->actingAs($admin)
        ->post(route('kontrak-sewaan.follow-up', $parent), [
            'kategori_permohonan' => ApplicationCategories::PINDAH,
        ])
        ->assertSessionHas('success');

    expect(RentalContract::query()->where('parent_contract_id', $parent->id)->count())->toBe(1);
});

test('follow-up cannot be reverted after it is sent to hq', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);
    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();
    $child->update(['workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ]);

    $this->actingAs($admin)
        ->delete(route('kontrak-sewaan.follow-up.cancel', $child))
        ->assertForbidden();

    expect(RentalContract::query()->find($child->id))->not->toBeNull();
});

test('a baru application cannot be reverted via the follow-up cancel route', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $contract = createApprovedParentContract('Johor');
    $contract->update(['workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI]);

    $this->actingAs($admin)
        ->delete(route('kontrak-sewaan.follow-up.cancel', $contract))
        ->assertForbidden();
});

test('admin negeri cannot revert a follow-up outside their negeri', function () {
    $johorAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $kelantanAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Kelantan']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($johorAdmin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::PINDAH,
    ]);
    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    $this->actingAs($kelantanAdmin)
        ->delete(route('kontrak-sewaan.follow-up.cancel', $child))
        ->assertForbidden();

    expect(RentalContract::query()->find($child->id))->not->toBeNull();
});

test('follow-up edit form shows remark and hides catatan tindakan', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('application.edit', $child))
        ->assertSuccessful()
        ->assertSee('Remark')
        ->assertDontSee('Catatan & Tindakan')
        ->assertDontSee('Rumusan Status Terkini')
        ->assertDontSee('Perincian Proses Cawangan Pembangunan')
        ->assertDontSee('id="remark_kategori"', false);
});

test('remark is required when updating a follow-up application', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor', 1000);

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::PINDAH,
    ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    $this->actingAs($admin)
        ->from(route('application.edit', $child))
        ->put(route('application.update', $child), [
            'negeri' => 'Johor',
            'nama_ptj' => 'Premis Baharu Pindah',
            'alamat_penuh' => 'Alamat baharu pindah',
            'nama_pemilik' => 'Pemilik Baharu',
            'jenis_bangunan' => \App\Support\BuildingTypes::values()[0],
            'kadar_sewa' => 1550,
            'keluasan_mp' => 200,
            'sah_sehingga' => now()->addYear()->format('Y-m-d'),
            'remark' => '',
        ])
        ->assertRedirect(route('application.edit', $child))
        ->assertSessionHasErrors('remark');
});

test('follow-up application shows hantar ke admin hq button in status list immediately', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    expect($child->showsSubmitToAdminHqButton())->toBeTrue()
        ->and($child->applicationStatusLabel())->toBe('Dalam Tindakan Lanjutan');

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Hantar ke Admin')
        ->assertSee('Dalam Tindakan Lanjutan');
});

test('pindah follow-up shows pindah status in status permohonan list', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::PINDAH,
    ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->firstOrFail();

    expect($child->applicationStatusLabel())->toBe('Dalam Tindakan Pindah');

    $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertSee('Dalam Tindakan Pindah')
        ->assertSee('Hantar ke Admin');
});

test('follow-up cannot be sent to hq without remark even when proceed steps are complete', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor');
    $child = RentalContract::query()->create([
        'parent_contract_id' => $parent->id,
        'premise_id' => Premise::query()->create([
            'nama_ptj' => 'Premis Susulan',
            'negeri' => 'Johor',
            'daerah' => 'Johor',
            'alamat_penuh' => 'Alamat',
            'nama_pemilik' => 'Pemilik',
        ])->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 1200,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
        'admin_proceed_progress' => completedLanjutanProceedProgress(),
    ]);

    expect($child->isProceedComplete())->toBeTrue()
        ->and($child->hasRequiredFollowUpRemark())->toBeFalse()
        ->and($child->isReadyToSendToHq())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('status-permohonan.submit-hq', $child))
        ->assertRedirect(route('application.edit', $child))
        ->assertSessionHas('error', 'Sila isi Remark sebelum menghantar permohonan ke Admin.');
});

test('remark is saved when updating a follow-up application', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);
    $parent = createApprovedParentContract('Johor', 1000);

    $this->actingAs($admin)->post(route('kontrak-sewaan.follow-up', $parent), [
        'kategori_permohonan' => ApplicationCategories::PINDAH,
    ]);

    $child = RentalContract::query()->where('parent_contract_id', $parent->id)->first();

    $this->actingAs($admin)->put(route('application.update', $child), [
        'negeri' => 'Johor',
        'nama_ptj' => 'Premis Baharu Pindah',
        'alamat_penuh' => 'Alamat baharu pindah',
        'nama_pemilik' => 'Pemilik Baharu',
        'jenis_bangunan' => \App\Support\BuildingTypes::values()[0],
        'kadar_sewa' => 1550,
        'keluasan_mp' => 200,
        'sah_sehingga' => now()->addYear()->format('Y-m-d'),
        'remark' => 'Pindah ke lokasi lebih strategik.',
    ])->assertRedirect(route('application.edit', $child));

    $child->refresh();

    expect($child->remark)->toBe('Pindah ke lokasi lebih strategik.')
        ->and($child->premise->nama_ptj)->toBe('Premis Baharu Pindah')
        ->and((float) $child->kadar_sewa_bulanan)->toBe(1550.0)
        ->and((float) $child->keluasan_mp)->toBe(200.0);
});
