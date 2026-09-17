<?php

use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\RentalContractNotificationView;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createListIndicatorContract(
    string $negeri,
    string $workflow,
    string $label,
    ?User $adminNegeri = null,
    ?\Carbon\Carbon $hqApprovedAt = null,
    ?\Carbon\Carbon $updatedAt = null,
    ?\Carbon\Carbon $createdAt = null,
): RentalContract {
    $premise = Premise::query()->create([
        'nama_ptj' => $label,
        'negeri' => $negeri,
        'daerah' => $negeri,
        'alamat_penuh' => 'Alamat',
        'nama_pemilik' => 'Pemilik',
    ]);

    $contract = RentalContract::query()->create([
        'premise_id' => $premise->id,
        'admin_negeri_user_id' => $adminNegeri?->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 0,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => $workflow,
        'kategori_permohonan' => ApplicationCategories::BARU,
        'hq_approved_at' => $hqApprovedAt,
    ]);

    $timestamps = [];

    if ($createdAt) {
        $timestamps['created_at'] = $createdAt;
    }

    if ($updatedAt) {
        $timestamps['updated_at'] = $updatedAt;
    }

    if ($timestamps !== []) {
        $contract->forceFill($timestamps)->saveQuietly();
    }

    return $contract->fresh(['adminNegeriUser', 'premise']);
}

test('status permohonan list marks unseen applications with orange edge and sorts by newest application date', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $unseenAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Negeri Lama Belum Dibuka']);
    $seenAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Negeri Baru Sudah Dibuka']);

    $olderUnseen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
        'Premis Lama Belum Dibuka',
        $unseenAdmin,
        createdAt: now()->subDay(),
        updatedAt: now()->subDay(),
    );
    $newerSeen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
        'Premis Baru Sudah Dibuka',
        $seenAdmin,
        createdAt: now(),
        updatedAt: now(),
    );

    RentalContractNotificationView::query()->create([
        'user_id' => $admin->id,
        'rental_contract_id' => $newerSeen->id,
        'viewed_at' => now(),
    ]);

    $html = $this->actingAs($admin)
        ->get(route('status-permohonan.index'))
        ->assertSuccessful()
        ->assertDontSee('Belum dibuka / baru masuk')
        ->assertDontSee('>Baru</span>', false)
        ->assertSee('Negeri Lama Belum Dibuka')
        ->assertSee('Negeri Baru Sudah Dibuka')
        ->getContent();

    expect(strpos($html, 'Negeri Baru Sudah Dibuka'))->toBeLessThan(strpos($html, 'Negeri Lama Belum Dibuka'))
        ->and($html)->toContain('glass-row-unseen')
        ->and($olderUnseen->isUnseenBy($admin))->toBeTrue()
        ->and($newerSeen->fresh()->isUnseenBy($admin))->toBeFalse();
});

test('kontrak sewaan list marks unseen contracts with orange edge and sorts by shortest remaining period', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $soonestUnseen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'Kontrak Baki Pendek',
        $admin,
        hqApprovedAt: now()->subDays(2),
    );
    $soonestUnseen->update(['sah_sehingga' => now()->addMonths(2)]);

    $laterSeen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'Kontrak Baki Panjang',
        $admin,
        hqApprovedAt: now(),
    );
    $laterSeen->update(['sah_sehingga' => now()->addMonths(18)]);

    RentalContractNotificationView::query()->create([
        'user_id' => $admin->id,
        'rental_contract_id' => $laterSeen->id,
        'viewed_at' => now(),
    ]);

    $html = $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertDontSee('Belum dibuka / baru masuk')
        ->assertDontSee('>Baru</span>', false)
        ->assertSee('Kontrak Baki Pendek')
        ->assertSee('Kontrak Baki Panjang')
        ->assertSee('bg-red-100 text-red-700', false)
        ->getContent();

    expect(strpos($html, 'Kontrak Baki Pendek'))->toBeLessThan(strpos($html, 'Kontrak Baki Panjang'))
        ->and($html)->toContain('glass-row-unseen')
        ->and($soonestUnseen->isUnseenBy($admin))->toBeTrue();
});
