<?php

namespace App\Models;

use App\Support\AdminProceedSteps;
use App\Support\ApplicationCategories;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalContract extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::updating(function (RentalContract $contract): void {
            if ($contract->isDirty('workflow_tahap') || $contract->isDirty('withdrawal_status')) {
                RentalContractNotificationView::query()
                    ->where('rental_contract_id', $contract->id)
                    ->delete();
            }
        });
    }

    protected $fillable = [
        'premise_id',
        'parent_contract_id',
        'submitted_by_user_id',
        'no_fail_rujukan',
        'tarikh_mula',
        'tarikh_tamat',
        'kadar_sewa_bulanan',
        'keluasan_mp',
        'status_aktif',
        'peringkat_proses',
        'tarikh_surat_niat',
        'catatan_hq',
        'remark',
        'workflow_tahap',
        'kategori_permohonan',
        'sah_sehingga',
        'admin_proceed_progress',
        'delete_reason',
        'deleted_by_user_id',
        'withdrawal_status',
        'withdrawal_reason',
        'withdrawal_requested_at',
        'withdrawal_requested_by_user_id',
        'withdrawal_resolved_at',
        'withdrawal_resolved_by_user_id',
        'withdrawal_resolution_note',
        'hq_approved_at',
        'hq_jrp_checklist',
        'superseded_at',
        'superseded_by_contract_id',
        'admin_negeri_user_id',
    ];

    public const PLACEHOLDER_CONTRACT_DATE = '1000-01-01';

    public const RENT_DIFFERENCE_THRESHOLD = 500;

    public const WITHDRAWAL_PENDING = 'pending';

    public const WITHDRAWAL_APPROVED = 'approved';

    public const WITHDRAWAL_REJECTED = 'rejected';

    public const WORKFLOW_MENUNGGU_PROCEED_NEGERI = 'menunggu_proceed_negeri';

    public const WORKFLOW_MENUNGGU_SEMAKAN_HQ = 'menunggu_semakan_hq';

    public const WORKFLOW_MENUNGGU_SEMAKAN_NEGERI = 'menunggu_semakan_negeri';

    /**
     * @return list<string>
     */
    public static function hqApprovedWorkflows(): array
    {
        return [
            self::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
        ];
    }

    public function isHqApproved(): bool
    {
        return in_array($this->workflow_tahap, self::hqApprovedWorkflows(), true);
    }

    /**
     * @param  Builder<RentalContract>  $query
     * @return Builder<RentalContract>
     */
    public function scopeHqApproved(Builder $query): Builder
    {
        return $query->whereIn('workflow_tahap', self::hqApprovedWorkflows());
    }

    /**
     * Contracts that are still current, i.e. not replaced by an approved
     * pindah/lanjutan follow-up application.
     *
     * @param  Builder<RentalContract>  $query
     * @return Builder<RentalContract>
     */
    public function scopeNotSuperseded(Builder $query): Builder
    {
        return $query->whereNull('superseded_at');
    }

    protected function casts(): array
    {
        return [
            'tarikh_mula' => 'date',
            'tarikh_tamat' => 'date',
            'tarikh_surat_niat' => 'date',
            'sah_sehingga' => 'date',
            'kadar_sewa_bulanan' => 'decimal:2',
            'keluasan_mp' => 'decimal:2',
            'admin_proceed_progress' => 'array',
            'withdrawal_requested_at' => 'datetime',
            'withdrawal_resolved_at' => 'datetime',
            'hq_approved_at' => 'datetime',
            'hq_jrp_checklist' => 'array',
            'superseded_at' => 'datetime',
        ];
    }

    public function premise(): BelongsTo
    {
        return $this->belongsTo(Premise::class, 'premise_id');
    }

    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_contract_id');
    }

    public function followUpApplications(): HasMany
    {
        return $this->hasMany(self::class, 'parent_contract_id');
    }

    public function supersededByContract(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_contract_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function adminNegeriUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_negeri_user_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function withdrawalRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawal_requested_by_user_id');
    }

    public function withdrawalResolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawal_resolved_by_user_id');
    }

    public function notificationViews(): HasMany
    {
        return $this->hasMany(RentalContractNotificationView::class);
    }

    public function isUnseenBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('notificationViews')) {
            return $this->notificationViews
                ->where('user_id', $user->id)
                ->isEmpty();
        }

        return ! $this->notificationViews()->where('user_id', $user->id)->exists();
    }

    /**
     * Prefer unseen (new) rows first, then most recently updated.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrderForUserList(Builder $query, User $user, string $recencyColumn = 'updated_at'): Builder
    {
        return $query
            ->orderByRaw(
                '(select count(*) from rental_contract_notification_views where rental_contract_notification_views.rental_contract_id = rental_contracts.id and rental_contract_notification_views.user_id = ?) asc',
                [$user->id]
            )
            ->orderByDesc($recencyColumn)
            ->orderByDesc('rental_contracts.id');
    }

    public function displayNamaPtj(): string
    {
        if (filled($this->premise?->nama_ptj)) {
            return (string) $this->premise->nama_ptj;
        }

        return '–';
    }

    public function displayPusatTanggungjawab(): ?string
    {
        if (! filled($this->premise?->nama_ptj)) {
            return null;
        }

        return (string) $this->premise->nama_ptj;
    }

    public function displayPremisePtjName(): string
    {
        if (filled($this->premise?->nama_ptj)) {
            return (string) $this->premise->nama_ptj;
        }

        return '–';
    }

    public function displayAdminNegeriName(): string
    {
        if (filled($this->adminNegeriUser?->name)) {
            return (string) $this->adminNegeriUser->name;
        }

        return '–';
    }

    /**
     * @return array{tindakan: ?string, rumusan_status: ?string}
     */
    public function applicationFormNotes(): array
    {
        $catatan = (string) ($this->catatan_hq ?? '');
        $tindakan = null;
        $rumusan = null;

        if (preg_match('/^Tindakan: (.+)$/m', $catatan, $matches)) {
            $tindakan = trim($matches[1]);
        }

        if (preg_match('/^Rumusan: (.+)$/m', $catatan, $matches)) {
            $rumusan = trim($matches[1]);
        }

        return [
            'tindakan' => $tindakan,
            'rumusan_status' => $rumusan,
        ];
    }

    public function processLogs(): HasMany
    {
        return $this->hasMany(ProcessLog::class, 'contract_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractDocument::class, 'contract_id');
    }

    public function isPendingProceed(): bool
    {
        return $this->workflow_tahap === self::WORKFLOW_MENUNGGU_PROCEED_NEGERI;
    }

    public function isProceedComplete(): bool
    {
        return AdminProceedSteps::allCompleted($this);
    }

    public function isReadyToSendToHq(): bool
    {
        return $this->isPendingProceed()
            && $this->isProceedComplete()
            && $this->hasRequiredFollowUpRemark();
    }

    /**
     * Whether the "Hantar ke Admin" action should appear in the list.
     * Follow-up applications (pindah/lanjutan) always show the button while
     * pending proceed; validation runs when the admin clicks submit.
     */
    public function showsSubmitToAdminHqButton(): bool
    {
        if (! $this->isPendingProceed()) {
            return false;
        }

        if ($this->isFollowUpApplication()) {
            return true;
        }

        return $this->isProceedComplete();
    }

    public function hasRequiredFollowUpRemark(): bool
    {
        if (! $this->isFollowUpApplication()) {
            return true;
        }

        return filled(trim((string) ($this->remark ?? '')));
    }

    public function isAwaitingFollowUpRemark(): bool
    {
        return $this->isPendingProceed()
            && $this->isProceedComplete()
            && $this->isFollowUpApplication()
            && ! $this->hasRequiredFollowUpRemark();
    }

    public function isPendingHqReview(): bool
    {
        return $this->workflow_tahap === self::WORKFLOW_MENUNGGU_SEMAKAN_HQ;
    }

    public function hasPendingWithdrawalRequest(): bool
    {
        return $this->withdrawal_status === self::WITHDRAWAL_PENDING;
    }

    public function hasRejectedWithdrawalRequest(): bool
    {
        return $this->withdrawal_status === self::WITHDRAWAL_REJECTED;
    }

    public function canRequestWithdrawal(): bool
    {
        return $this->isPendingHqReview()
            && ! $this->hasPendingWithdrawalRequest();
    }

    public function canBeDeletedByAdminNegeri(): bool
    {
        return ! $this->isPendingHqReview()
            && ! $this->hasPendingWithdrawalRequest();
    }

    public function isPendingAdminReview(): bool
    {
        return $this->workflow_tahap === self::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI;
    }

    public function isFollowUpApplication(): bool
    {
        return $this->parent_contract_id !== null;
    }

    public function isPindah(): bool
    {
        return $this->kategori_permohonan === ApplicationCategories::PINDAH;
    }

    public function isLanjutan(): bool
    {
        return $this->kategori_permohonan === ApplicationCategories::LANJUTAN;
    }

    public function isSuperseded(): bool
    {
        return $this->superseded_at !== null;
    }

    /**
     * Whether an unresolved pindah/lanjutan application already exists for this
     * contract, to prevent starting a duplicate follow-up.
     */
    public function hasPendingFollowUp(): bool
    {
        return $this->pendingFollowUp() !== null;
    }

    public function pendingFollowUp(): ?self
    {
        if ($this->relationLoaded('followUpApplications')) {
            return $this->followUpApplications->first();
        }

        return $this->followUpApplications()->first();
    }

    public function pendingFollowUpInProgressLabel(): string
    {
        return $this->pendingFollowUp()?->followUpInProgressStatusLabel()
            ?? 'Dalam Tindakan Lanjutan/Pindah';
    }

    public function followUpInProgressStatusLabel(): string
    {
        return match ($this->kategori_permohonan) {
            ApplicationCategories::LANJUTAN => 'Dalam Tindakan Lanjutan',
            ApplicationCategories::PINDAH => 'Dalam Tindakan Pindah',
            default => 'Dalam Tindakan Lanjutan/Pindah',
        };
    }

    public function parentRent(): ?float
    {
        $parentRent = $this->parentContract?->kadar_sewa_bulanan;

        return $parentRent === null ? null : (float) $parentRent;
    }

    public function rentDifferenceFromParent(): ?float
    {
        $parentRent = $this->parentRent();

        if ($parentRent === null) {
            return null;
        }

        return (float) $this->kadar_sewa_bulanan - $parentRent;
    }

    /**
     * @return 'below'|'above'|null
     */
    public function rentDifferenceBand(): ?string
    {
        $difference = $this->rentDifferenceFromParent();

        if ($difference === null) {
            return null;
        }

        return abs($difference) < self::RENT_DIFFERENCE_THRESHOLD ? 'below' : 'above';
    }

    public function rentDifferenceBandLabel(): ?string
    {
        return match ($this->rentDifferenceBand()) {
            'below' => 'Perbezaan < RM500',
            'above' => 'Perbezaan ≥ RM500',
            default => null,
        };
    }

    /**
     * @return 'baru'|'sedia_hq'|'menunggu_hq'|'semakan'|'lain'
     */
    public function adminListStatus(): string
    {
        if ($this->isReadyToSendToHq()) {
            return 'sedia_hq';
        }

        if ($this->isPendingProceed()) {
            return 'baru';
        }

        if ($this->isPendingAdminReview()) {
            return 'semakan';
        }

        if ($this->isPendingHqReview()) {
            return 'menunggu_hq';
        }

        return 'lain';
    }

    public function adminListRowClasses(?User $viewer = null): string
    {
        $unseenClass = $viewer && $this->isUnseenBy($viewer) ? ' glass-row-unseen' : '';

        if ($this->isReadyToSendToHq()) {
            return 'glass-row-ready-hq'.$unseenClass;
        }

        if ($this->isPendingProceed()) {
            return 'glass-row-pending'.$unseenClass;
        }

        if ($this->isPendingHqReview()) {
            return ($this->hasPendingWithdrawalRequest()
                ? 'glass-row-withdrawal-pending'
                : 'glass-row-hq-review').$unseenClass;
        }

        return 'glass-row-neutral'.$unseenClass;
    }

    public function adminListProgressPercentClass(): string
    {
        if ($this->isReadyToSendToHq()) {
            return 'font-medium text-emerald-700';
        }

        if ($this->isPendingHqReview()) {
            return 'font-medium text-indigo-700';
        }

        return 'font-medium text-slate-700';
    }

    public function adminListProgressBarFillClass(): string
    {
        $progressPercent = $this->proceedProgressPercent();

        if ($this->isPendingHqReview()) {
            return 'glass-progress-fill-indigo';
        }

        if ($this->isReadyToSendToHq() || $progressPercent >= 100) {
            return 'glass-progress-fill-emerald';
        }

        if ($progressPercent > 0) {
            return 'glass-progress-fill-amber';
        }

        return '';
    }

    public function adminListProgressWidthPercent(): int
    {
        if ($this->isReadyToSendToHq() || $this->isPendingHqReview()) {
            return 100;
        }

        return $this->proceedProgressPercent();
    }

    public function applicationStatusLabel(?User $viewer = null): string
    {
        if ($this->hasPendingWithdrawalRequest()) {
            return $viewer?->isAdminHq() === true
                ? 'Negeri memohon untuk tarik semula'
                : 'Menunggu kelulusan tarik semula';
        }

        if ($this->hasRejectedWithdrawalRequest() && $this->isPendingHqReview()) {
            return 'Permohonan tarik semula ditolak';
        }

        if ($this->isFollowUpApplication() && $this->isPendingProceed()) {
            return $this->followUpInProgressStatusLabel();
        }

        if ($this->isReadyToSendToHq()) {
            return 'Permohonan sedia untuk dihantar ke Admin';
        }

        return match ($this->workflow_tahap) {
            self::WORKFLOW_MENUNGGU_PROCEED_NEGERI => 'Menunggu langkah tindakan',
            self::WORKFLOW_MENUNGGU_SEMAKAN_HQ => 'Menunggu semakan HQ',
            self::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI => 'Menunggu semakan pentadbir negeri',
            default => match ($this->status_aktif) {
                'aktif' => 'Aktif',
                'tamat_tempoh' => 'Tamat tempoh',
                'dalam_proses' => 'Dalam proses',
                'batal' => 'Batal',
                default => ucwords(str_replace('_', ' ', (string) $this->status_aktif)),
            },
        };
    }

    public function applicationStatusBadgeClass(): string
    {
        if ($this->hasPendingWithdrawalRequest()) {
            return 'glass-status-badge glass-status-badge--withdrawal';
        }

        if ($this->hasRejectedWithdrawalRequest() && $this->isPendingHqReview()) {
            return 'glass-status-badge glass-status-badge--withdrawal-rejected';
        }

        if ($this->isReadyToSendToHq()) {
            return 'glass-status-badge glass-status-badge--ready';
        }

        if ($this->isFollowUpApplication() && $this->isPendingProceed()) {
            return 'glass-status-badge glass-status-badge--pending';
        }

        if ($this->isPendingProceed()) {
            return 'glass-status-badge glass-status-badge--pending';
        }

        if ($this->isPendingHqReview()) {
            return 'glass-status-badge glass-status-badge--hq-review';
        }

        return 'glass-status-badge';
    }

    public function usesPlaceholderContractDates(): bool
    {
        return $this->tarikh_mula?->toDateString() === self::PLACEHOLDER_CONTRACT_DATE;
    }

    public function proceedProgressPercent(): int
    {
        return AdminProceedSteps::progressSummary($this)['progressPercent'];
    }

    public function workflowLabel(): string
    {
        return match ($this->workflow_tahap) {
            self::WORKFLOW_MENUNGGU_PROCEED_NEGERI => $this->isProceedComplete()
                ? 'Permohonan sedia untuk dihantar ke HQ'
                : 'Menunggu langkah tindakan pentadbir negeri',
            self::WORKFLOW_MENUNGGU_SEMAKAN_HQ => 'Menunggu semakan HQ',
            self::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI => 'Menunggu semakan pentadbir negeri',
            default => $this->workflow_tahap ?? '–',
        };
    }

    public function getTempohBulanAttribute(): int
    {
        return $this->tarikh_mula && $this->tarikh_tamat && ! $this->usesPlaceholderContractDates()
            ? (int) $this->tarikh_mula->diffInMonths($this->tarikh_tamat)
            : 0;
    }

    public function getBakiBulanAttribute(): ?int
    {
        if (! $this->tarikh_tamat || $this->usesPlaceholderContractDates()) {
            return null;
        }
        $today = Carbon::today();
        if ($this->tarikh_tamat->isPast()) {
            return (int) $this->tarikh_tamat->diffInMonths($today, false);
        }

        return (int) $today->diffInMonths($this->tarikh_tamat, false);
    }

    public function getBakiHariAttribute(): ?int
    {
        if (! $this->tarikh_tamat || $this->usesPlaceholderContractDates()) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->tarikh_tamat, false);
    }

    public function isExpiringWithinEightMonths(): bool
    {
        $bakiHari = $this->baki_hari;

        return $bakiHari !== null && $bakiHari >= 0 && $bakiHari <= 240;
    }

    public function isExpired(): bool
    {
        return $this->tarikh_tamat
            && ! $this->usesPlaceholderContractDates()
            && $this->tarikh_tamat->isPast();
    }

    public function contractEndDate(): ?Carbon
    {
        if ($this->tarikh_tamat && ! $this->usesPlaceholderContractDates()) {
            return $this->tarikh_tamat;
        }

        return $this->sah_sehingga;
    }

    public function contractPeriodLabel(): string
    {
        if (! $this->usesPlaceholderContractDates()) {
            $months = $this->tempoh_bulan;

            if ($months > 0) {
                return $months.' bulan';
            }

            if ($this->tarikh_mula && $this->tarikh_tamat) {
                return $this->tarikh_mula->format('d/m/Y').' – '.$this->tarikh_tamat->format('d/m/Y');
            }
        }

        if ($this->sah_sehingga) {
            return 'Sah sehingga '.$this->sah_sehingga->format('d/m/Y');
        }

        return '–';
    }

    public function daysUntilContractEndLabel(): string
    {
        $end = $this->contractEndDate();

        if (! $end) {
            return '–';
        }

        $days = (int) Carbon::today()->diffInDays($end, false);

        if ($days < 0) {
            return 'Tamat tempoh ('.abs($days).' hari lalu)';
        }

        if ($days === 0) {
            return 'Tamat hari ini';
        }

        return $days.' hari lagi';
    }

    public function daysUntilContractEnd(): ?int
    {
        $end = $this->contractEndDate();

        if (! $end) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($end, false);
    }

    public function isContractEndWithinEightMonths(): bool
    {
        $days = $this->daysUntilContractEnd();

        return $days !== null && $days >= 0 && $days <= 240;
    }
}
