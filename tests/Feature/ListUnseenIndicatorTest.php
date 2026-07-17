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

    if ($updatedAt) {
        $contract->forceFill(['updated_at' => $updatedAt])->saveQuietly();
    }

    return $contract->fresh(['adminNegeriUser', 'premise']);
}

test('status permohonan list marks unseen applications with orange edge and sorts them first', function () {
    $admin = User::factory()->create(['role' => 'admin_hq']);
    $unseenAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Negeri Lama Belum Dibuka']);
    $seenAdmin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor', 'name' => 'Negeri Baru Sudah Dibuka']);

    $olderUnseen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
        'Premis Lama Belum Dibuka',
        $unseenAdmin,
        updatedAt: now()->subDay(),
    );
    $newerSeen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
        'Premis Baru Sudah Dibuka',
        $seenAdmin,
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

    expect(strpos($html, 'Negeri Lama Belum Dibuka'))->toBeLessThan(strpos($html, 'Negeri Baru Sudah Dibuka'))
        ->and($html)->toContain('glass-row-unseen')
        ->and($olderUnseen->isUnseenBy($admin))->toBeTrue()
        ->and($newerSeen->fresh()->isUnseenBy($admin))->toBeFalse();
});

test('kontrak sewaan list marks unseen contracts with orange edge and sorts by latest approval', function () {
    $admin = User::factory()->create(['role' => 'admin_negeri', 'negeri' => 'Johor']);

    $olderUnseen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'Kontrak Lama Belum Dibuka',
        $admin,
        hqApprovedAt: now()->subDays(2),
    );
    $newerSeen = createListIndicatorContract(
        'Johor',
        RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'Kontrak Baru Sudah Dibuka',
        $admin,
        hqApprovedAt: now(),
    );

    RentalContractNotificationView::query()->create([
        'user_id' => $admin->id,
        'rental_contract_id' => $newerSeen->id,
        'viewed_at' => now(),
    ]);

    $html = $this->actingAs($admin)
        ->get(route('kontrak-sewaan.index'))
        ->assertSuccessful()
        ->assertDontSee('Belum dibuka / baru masuk')
        ->assertDontSee('>Baru</span>', false)
        ->assertSee('Kontrak Lama Belum Dibuka')
        ->assertSee('Kontrak Baru Sudah Dibuka')
        ->getContent();

    expect(strpos($html, 'Kontrak Lama Belum Dibuka'))->toBeLessThan(strpos($html, 'Kontrak Baru Sudah Dibuka'))
        ->and($html)->toContain('glass-row-unseen')
        ->and($olderUnseen->isUnseenBy($admin))->toBeTrue();
});
