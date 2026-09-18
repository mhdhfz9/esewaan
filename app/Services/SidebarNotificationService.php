<?php

namespace App\Services;

use App\Models\RentalContract;
use App\Models\RentalContractNotificationView;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SidebarNotificationService
{
    /**
     * @return array{
     *     status_permohonan: int,
     *     kontrak_sewaan: int,
     *     hq_pending_withdrawal: int,
     *     admin_pending_review: int,
     *     inbox_total: int
     * }
     */
    public function forUser(User $user): array
    {
        $statusPermohonan = 0;
        $kontrakSewaan = 0;
        $hqPendingWithdrawal = 0;
        $inboxTotal = 0;

        if ($user->isAdmin()) {
            $statusPermohonan = $this->unseenCount($user, $this->statusPermohonanQuery($user));
            $kontrakSewaan = $this->unseenCount($user, $this->kontrakSewaanQuery($user));
            $hqPendingWithdrawal = $user->isAdminHq()
                ? $this->unseenCount($user, $this->hqPendingWithdrawalQuery())
                : 0;
            $inboxTotal = $this->unseenInboxContractIds($user)->count();
        }

        return [
            'status_permohonan' => $statusPermohonan,
            'kontrak_sewaan' => $kontrakSewaan,
            'hq_pending_withdrawal' => $hqPendingWithdrawal,
            // Legacy key kept for existing callers/tests.
            'admin_pending_review' => $statusPermohonan,
            'inbox_total' => $inboxTotal,
        ];
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     category: string,
     *     category_label: string,
     *     title: string,
     *     message: string,
     *     url: string,
     *     occurred_at: \Illuminate\Support\Carbon|null
     * }>
     */
    public function inboxItems(User $user, int $limit = 50): Collection
    {
        if (! $user->isAdmin()) {
            return collect();
        }

        $items = collect();

        if ($user->isAdminHq()) {
            $withdrawalContracts = $this->unseenContracts($user, $this->hqPendingWithdrawalQuery())->get();
            foreach ($withdrawalContracts as $contract) {
                $items->push($this->toInboxItem(
                    $contract,
                    $user,
                    'withdrawal',
                    'Permohonan Tarik Semula',
                ));
            }
        }

        $statusContracts = $this->unseenContracts($user, $this->statusPermohonanQuery($user))->get();
        foreach ($statusContracts as $contract) {
            $items->push($this->toInboxItem(
                $contract,
                $user,
                'status_permohonan',
                $user->isAdminHq() ? 'Senarai Permohonan' : 'Status Permohonan',
            ));
        }

        $kontrakContracts = $this->unseenContracts($user, $this->kontrakSewaanQuery($user))->get();
        foreach ($kontrakContracts as $contract) {
            $items->push($this->toInboxItem(
                $contract,
                $user,
                'kontrak_sewaan',
                'Kontrak Sewaan',
            ));
        }

        return $items
            ->unique('id')
            ->sortByDesc(fn (array $item): int => $item['occurred_at']?->getTimestamp() ?? 0)
            ->values()
            ->take($limit);
    }

    public function markInboxAsViewed(User $user): int
    {
        if (! $user->isAdmin()) {
            return 0;
        }

        $contractIds = $this->unseenInboxContractIds($user);

        foreach ($contractIds as $contractId) {
            RentalContractNotificationView::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'rental_contract_id' => $contractId,
                ],
                [
                    'viewed_at' => now(),
                ],
            );
        }

        return $contractIds->count();
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
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function unseenInboxContractIds(User $user): Collection
    {
        $ids = $this->unseenContracts($user, $this->statusPermohonanQuery($user))->pluck('id');

        if ($user->isAdminHq()) {
            $ids = $ids->merge(
                $this->unseenContracts($user, $this->hqPendingWithdrawalQuery())->pluck('id')
            );
        }

        return $ids
            ->merge($this->unseenContracts($user, $this->kontrakSewaanQuery($user))->pluck('id'))
            ->unique()
            ->values();
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
     * @param  Builder<RentalContract>  $query
     * @return Builder<RentalContract>
     */
    private function unseenContracts(User $user, Builder $query): Builder
    {
        return (clone $query)
            ->with(['premise'])
            ->whereDoesntHave('notificationViews', fn (Builder $viewQuery) => $viewQuery->where('user_id', $user->id))
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{
     *     id: int,
     *     category: string,
     *     category_label: string,
     *     title: string,
     *     message: string,
     *     url: string,
     *     occurred_at: \Illuminate\Support\Carbon|null
     * }
     */
    private function toInboxItem(RentalContract $contract, User $user, string $category, string $categoryLabel): array
    {
        $premiseName = $contract->premise?->nama_ptj ?: 'Premis belum dinamakan';

        $title = match ($category) {
            'withdrawal' => 'Permohonan tarik semula menunggu keputusan',
            'kontrak_sewaan' => 'Kontrak sewaan baharu / belum dilihat',
            default => $contract->applicationStatusLabel($user),
        };

        $url = match (true) {
            $category === 'kontrak_sewaan' => route('kontrak-sewaan.show', $contract, false),
            $contract->isPendingProceed() => route('application.edit', $contract, false),
            default => route('status-permohonan.review', $contract, false),
        };

        return [
            'id' => $contract->id,
            'category' => $category,
            'category_label' => $categoryLabel,
            'title' => $title,
            'message' => $premiseName.' · '.($contract->premise?->negeri ?? '–'),
            'url' => $url,
            'occurred_at' => $contract->updated_at,
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     category: string,
     *     category_label: string,
     *     title: string,
     *     message: string,
     *     url: string,
     *     occurred_at: string|null
     * }>
     */
    public function inboxItemsPayload(User $user, int $limit = 8): array
    {
        return $this->inboxItems($user, $limit)
            ->map(fn (array $item): array => [
                'id' => $item['id'],
                'category' => $item['category'],
                'category_label' => $item['category_label'],
                'title' => $item['title'],
                'message' => $item['message'],
                'url' => $item['url'],
                'occurred_at' => $item['occurred_at']?->toIso8601String(),
            ])
            ->values()
            ->all();
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
            ->kontrakNotExpired()
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
