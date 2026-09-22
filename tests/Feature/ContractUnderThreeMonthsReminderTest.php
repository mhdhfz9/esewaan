<?php

use App\Mail\ContractUnderThreeMonthsReminder;
use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Support\ApplicationCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createUnderThreeMonthsContract(User $negeriAdmin, int $daysRemaining = 45): RentalContract
{
    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Bawah 3 Bulan',
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
        'kadar_sewa_bulanan' => 1000,
        'status_aktif' => 'aktif',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        'kategori_permohonan' => ApplicationCategories::BARU,
        'hq_approved_at' => now()->subMonths(6),
        'sah_sehingga' => now()->addDays($daysRemaining)->toDateString(),
    ]);
}

test('weekly reminder emails negeri admins for under three month contracts without follow-up', function () {
    Mail::fake();

    $negeri = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Johor',
        'email' => 'negeri-johor@example.com',
    ]);
    $otherNegeri = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Melaka',
        'email' => 'negeri-melaka@example.com',
    ]);
    $hq = User::factory()->create([
        'role' => 'admin_hq',
        'email' => 'hq@example.com',
    ]);

    createUnderThreeMonthsContract($negeri, 40);
    createUnderThreeMonthsContract($otherNegeri, 20);

    Artisan::call('contracts:send-under-three-months-reminder');

    Mail::assertSent(ContractUnderThreeMonthsReminder::class, function (ContractUnderThreeMonthsReminder $mail) use ($negeri): bool {
        return $mail->hasTo($negeri->email)
            && $mail->contracts->count() === 1
            && $mail->contracts->first()?->premise?->negeri === 'Johor';
    });

    Mail::assertSent(ContractUnderThreeMonthsReminder::class, function (ContractUnderThreeMonthsReminder $mail) use ($otherNegeri): bool {
        return $mail->hasTo($otherNegeri->email)
            && $mail->contracts->count() === 1
            && $mail->contracts->first()?->premise?->negeri === 'Melaka';
    });

    Mail::assertNotSent(ContractUnderThreeMonthsReminder::class, function (ContractUnderThreeMonthsReminder $mail) use ($hq): bool {
        return $mail->hasTo($hq->email);
    });
});

test('weekly reminder skips contracts once negeri starts follow-up action', function () {
    Mail::fake();

    $negeri = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Johor',
        'email' => 'negeri-action@example.com',
    ]);

    $contract = createUnderThreeMonthsContract($negeri, 30);

    RentalContract::query()->create([
        'premise_id' => $contract->premise_id,
        'parent_contract_id' => $contract->id,
        'admin_negeri_user_id' => $negeri->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 1000,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
        'kategori_permohonan' => ApplicationCategories::LANJUTAN,
    ]);

    Artisan::call('contracts:send-under-three-months-reminder');

    Mail::assertNothingSent();
});

test('weekly reminder skips contracts with more than three months remaining', function () {
    Mail::fake();

    $negeri = User::factory()->create([
        'role' => 'admin_negeri',
        'negeri' => 'Johor',
        'email' => 'negeri-safe@example.com',
    ]);

    createUnderThreeMonthsContract($negeri, 120);

    Artisan::call('contracts:send-under-three-months-reminder');

    Mail::assertNothingSent();
});

test('under three months reminder is scheduled every monday at 9am malaysia time', function () {
    $events = collect(app('Illuminate\Console\Scheduling\Schedule')->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'contracts:send-under-three-months-reminder'));

    expect($events)->not->toBeEmpty();

    $event = $events->first();

    expect($event->expression)->toBe('0 9 * * 1')
        ->and($event->timezone)->toBe('Asia/Kuala_Lumpur');
});
