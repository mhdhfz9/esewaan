<?php

namespace App\Services;

use App\Models\RentalContract;
use App\Models\RentalContractNotificationView;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SidebarNotificationService
{
    /**
     * @return array{
     *     status_permohonan: int,
     *     kontrak_sewaan: int,
     *     hq_pending_withdrawal: int,
     *     admin_pending_review: int
     * }
     */
    public function forUser(User $user): array
    {
        $statusPermohonan = 0;
        $kontrakSewaan = 0;
        $hqPendingWithdrawal = 0;

        if ($user->isAdmin()) {
            $statusPermohonan = $this->unseenCount($user, $this->statusPermohonanQuery($user));
            $kontrakSewaan = $this->unseenCount($user, $this->kontrakSewaanQuery($user));
            $hqPendingWithdrawal = $user->isAdminHq()
                ? $this->unseenCount($user, $this->hqPendingWithdrawalQuery())
                : 0;
        }

        return [
            'status_permohonan' => $statusPermohonan,
            'kontrak_sewaan' => $kontrakSewaan,
            'hq_pending_withdrawal' => $hqPendingWithdrawal,
            // Legacy key kept for existing callers/tests.
            'admin_pending_review' => $statusPermohonan,
        ];
    }

    public function markContractAsViewed(User $user, RentalContract $contract): void
    {
        RentalContractNotificationView::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'rental_contract_id' => $contract->id,
            ],
            [
                'viewed_at' => now(),
            ],
        );
    }

    /**
     * @param  Builder<RentalContract>  $query
     */
    private function unseenCount(User $user, Builder $query): int
    {
        return (clone $query)
            ->whereDoesntHave('notificationViews', fn (Builder $viewQuery) => $viewQuery->where('user_id', $user->id))
            ->count();
    }

    /**
     * Unseen items that appear in Status Permohonan / Senarai Permohonan.
     *
     * @return Builder<RentalContract>
     */
    private function statusPermohonanQuery(User $user): Builder
    {
        if ($user->isAdminHq()) {
            return RentalContract::query()
                ->whereIn('workflow_tahap', array_merge(
                    [RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ],
                    RentalContract::draftAgreementWorkflows(),
                ));
        }

        return RentalContract::query()
            ->when(
                $user->isAdminNegeri() && filled($user->negeri),
                fn (Builder $query) => $query->whereHas('premise', fn (Builder $premiseQuery) => $premiseQuery->where('negeri', $user->negeri))
            )
            ->whereIn('workflow_tahap', array_merge(
                [RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI],
                RentalContract::draftAgreementWorkflows(),
            ));
    }

    /**
     * Unseen items that appear in Senarai Kontrak Sewaan.
     *
     * @return Builder<RentalContract>
     */
    private function kontrakSewaanQuery(User $user): Builder
    {
        return RentalContract::query()
            ->hqApproved()
            ->notSuperseded()
            ->when(
                $user->isAdminNegeri() && filled($user->negeri),
                fn (Builder $query) => $query->whereHas('premise', fn (Builder $premiseQuery) => $premiseQuery->where('negeri', $user->negeri))
            );
    }

    /**
     * @return Builder<RentalContract>
     */
    private function hqPendingWithdrawalQuery(): Builder
    {
        return RentalContract::query()
            ->where('withdrawal_status', RentalContract::WITHDRAWAL_PENDING);
    }
}
