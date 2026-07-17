<?php

namespace App\Models;

use App\Support\PremiseStatuses;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Premise extends Model
{
    protected $fillable = [
        'nama_ptj',
        'negeri',
        'daerah',
        'alamat_penuh',
        'jenis_bangunan',
        'status_premis',
        'tarikh_mula',
        'tarikh_akhir',
        'kadar_sewa',
        'koordinat_lat',
        'koordinat_long',
        'nama_pemilik',
        'no_hak_milik',
        'status_pemilikan',
        'catatan_hak_milik',
        'alamat_pemilik',
        'no_telefon_pemilik',
        'bank_pemilik',
        'no_akaun_bank',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_mula' => 'date',
            'tarikh_akhir' => 'date',
            'kadar_sewa' => 'decimal:2',
            'koordinat_lat' => 'decimal:8',
            'koordinat_long' => 'decimal:8',
        ];
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class, 'premise_id');
    }

    public function statusLabel(): string
    {
        return PremiseStatuses::all()[$this->status_premis] ?? ($this->status_premis ?? '–');
    }

    public function getBakiBulanAttribute(): ?int
    {
        if (! $this->tarikh_mula || ! $this->tarikh_akhir) {
            return null;
        }

        $today = Carbon::today();

        if ($this->tarikh_akhir->isPast()) {
            return 0;
        }

        return (int) $today->diffInMonths($this->tarikh_akhir, false);
    }
}
