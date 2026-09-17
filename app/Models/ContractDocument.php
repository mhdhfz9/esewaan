<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractDocument extends Model
{
    public const JENIS_DRAF_PERJANJIAN = 'draf_perjanjian';

    public const SEMAKAN_MENUNGGU = 'menunggu';

    public const SEMAKAN_DILULUSKAN = 'diluluskan';

    public const SEMAKAN_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'contract_id',
        'nama_fail',
        'path',
        'jenis',
        'semakan_round',
        'semakan_status',
        'user_id',
    ];

    public function semakanLabel(): ?string
    {
        if ($this->semakan_round === null) {
            return null;
        }

        return 'Semakan '.$this->semakan_round;
    }

    public function semakanStatusLabel(): string
    {
        return match ($this->resolvedSemakanStatus()) {
            self::SEMAKAN_DILULUSKAN => 'Diluluskan',
            self::SEMAKAN_DIBATALKAN => 'Pindaan berdasarkan ulasan PUU',
            default => 'Menunggu Semakan PUU',
        };
    }

    public function semakanStatusBadgeClass(): string
    {
        return match ($this->resolvedSemakanStatus()) {
            self::SEMAKAN_DILULUSKAN => 'glass-status-badge glass-status-badge--ready',
            self::SEMAKAN_DIBATALKAN => 'glass-status-badge glass-status-badge--withdrawal-rejected',
            default => 'glass-status-badge glass-status-badge--hq-review',
        };
    }

    public function resolvedSemakanStatus(): string
    {
        if (filled($this->semakan_status)) {
            return (string) $this->semakan_status;
        }

        $contract = $this->relationLoaded('rentalContract')
            ? $this->rentalContract
            : $this->rentalContract()->first(['id', 'workflow_tahap', 'semakan_count']);

        if (! $contract || $this->semakan_round === null) {
            return self::SEMAKAN_MENUNGGU;
        }

        if ($contract->isDrafPerjanjianLulus() && (int) $this->semakan_round === (int) $contract->semakan_count) {
            return self::SEMAKAN_DILULUSKAN;
        }

        if ((int) $this->semakan_round < (int) $contract->semakan_count) {
            return self::SEMAKAN_DIBATALKAN;
        }

        if ($contract->isSemakanPuu() && (int) $this->semakan_round === (int) $contract->semakan_count) {
            return self::SEMAKAN_MENUNGGU;
        }

        return self::SEMAKAN_MENUNGGU;
    }

    public static function markSemakanOutcome(RentalContract $contract, string $status): void
    {
        self::query()
            ->where('contract_id', $contract->id)
            ->where('jenis', self::JENIS_DRAF_PERJANJIAN)
            ->where('semakan_round', $contract->semakan_count)
            ->update(['semakan_status' => $status]);
    }

    public function downloadUrl(): string
    {
        return route('documents.show', [
            'contract' => $this->contract_id,
            'document' => $this->id,
        ], absolute: false);
    }

    /**
     * @deprecated Use downloadUrl() instead.
     */
    public function publicUrl(): string
    {
        return $this->downloadUrl();
    }

    public function rentalContract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
