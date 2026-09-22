<?php

use App\Models\ActivityLog;
use App\Models\ContractDocument;
use App\Models\Premise;
use App\Models\ProcessLog;
use App\Models\RentalContract;
use App\Models\RentalContractNotificationView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('maps domain models to Malay module table names', function () {
    expect((new RentalContract)->getTable())->toBe('status_permohonan');
    expect((new Premise)->getTable())->toBe('premis');
    expect((new ContractDocument)->getTable())->toBe('dokumen_kontrak');
    expect((new ActivityLog)->getTable())->toBe('log_aktiviti');
    expect((new ProcessLog)->getTable())->toBe('log_proses');
    expect((new RentalContractNotificationView)->getTable())->toBe('notifikasi_status_permohonan');
});

it('creates records on renamed tables after migrations', function () {
    expect(Schema::hasTable('status_permohonan'))->toBeTrue();
    expect(Schema::hasTable('premis'))->toBeTrue();

    $premise = Premise::query()->create([
        'nama_ptj' => 'Premis Ujian',
        'negeri' => 'Johor',
        'daerah' => 'Johor Bahru',
        'alamat_penuh' => 'Alamat',
        'nama_pemilik' => 'Pemilik',
    ]);

    $contract = RentalContract::query()->create([
        'premise_id' => $premise->id,
        'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
        'kadar_sewa_bulanan' => 100,
        'status_aktif' => 'dalam_proses',
        'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
    ]);

    expect($contract->fresh()->premise?->nama_ptj)->toBe('Premis Ujian');
});
