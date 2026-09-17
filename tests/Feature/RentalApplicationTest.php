<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validApplicationPayload(string $negeri = 'Johor', string $kategori = 'baru'): array
{
    return [
        'kategori_permohonan' => $kategori,
        'negeri' => $negeri,
        'nama_ptj' => 'Premis Ujian',
        'alamat_penuh' => 'Alamat penuh ujian',
        'nama_pemilik' => 'Pemilik Ujian',
        'jenis_bangunan' => 'kompleks kerajaan',
        'kadar_sewa' => '1500.00',
        'keluasan_mp' => '120.50',
        'tarikh_mula_tawaran' => now()->format('Y-m-d'),
        'sah_sehingga' => now()->addYear()->format('Y-m-d'),
    ];
}

test('admin hq cannot access permohonan baru form', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($admin)
        ->get(route('application.form'))
        ->assertForbidden();
});

test('admin hq cannot submit rental application', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);

    $this->actingAs($admin)
        ->post(route('application.store'), validApplicationPayload('Selangor'))
        ->assertForbidden();
});

test('admin negeri can submit permohonan baru and is redirected to tindakan page', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor']);

    $this->actingAs($admin)
        ->post(route('application.store'), validApplicationPayload('Selangor'))
        ->assertRedirect(route('admin-proceed.show', RentalContract::query()->first()))
        ->assertSessionHas('success');

    $contract = RentalContract::query()->first();
    expect($contract)->not->toBeNull()
        ->and($contract->submitted_by_user_id)->toBeNull()
        ->and($contract->workflow_tahap)->toBe(RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI)
        ->and($contract->usesPlaceholderContractDates())->toBeTrue()
        ->and($contract->kategori_permohonan)->toBe('baru')
        ->and((float) $contract->keluasan_mp)->toBe(120.5)
        ->and($contract->tarikh_mula_tawaran?->format('Y-m-d'))->toBe(now()->format('Y-m-d'))
        ->and($contract->premise?->nama_ptj)->toBe('Premis Ujian');

    expect(Premise::query()->count())->toBe(1);
});

test('permohonan baru form allows selecting kategori', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor']);

    $this->actingAs($admin)
        ->get(route('application.form'))
        ->assertSuccessful()
        ->assertSee('— Pilih kategori —', false)
        ->assertSee('name="kategori_permohonan"', false)
        ->assertSee('name="keluasan_mp"', false)
        ->assertSee('id="keluasan_kps"', false)
        ->assertSee('Nama Premis')
        ->assertSee('>Baru</option>', false)
        ->assertSee('>Pindah</option>', false)
        ->assertSee('>Lanjutan</option>', false);
});

test('admin negeri can submit permohonan with selected kategori', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Selangor']);
    $payload = validApplicationPayload('Selangor', 'pindah');

    $this->actingAs($admin)
        ->post(route('application.store'), $payload)
        ->assertRedirect(route('admin-proceed.show', RentalContract::query()->first()))
        ->assertSessionHas('success');

    expect(RentalContract::query()->first()?->kategori_permohonan)->toBe('pindah');
});

test('admin negeri can submit permohonan baru only for their own negeri', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $this->actingAs($admin)
        ->post(route('application.store'), validApplicationPayload('Johor'))
        ->assertRedirect(route('admin-proceed.show', RentalContract::query()->first()));

    $invalidPayload = validApplicationPayload('Johor');
    $invalidPayload['negeri'] = 'Perak';

    $this->actingAs($admin)
        ->post(route('application.store'), $invalidPayload)
        ->assertSessionHasErrors('negeri');
});
