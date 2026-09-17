<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithTablePartial;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\SidebarNotificationService;
use App\Support\AdminProceedSteps;
use App\Support\RentalContractListSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KontrakSewaanController extends Controller
{
    use RespondsWithTablePartial;

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $tab = $request->input('tab', 'active');
        $tab = in_array($tab, ['active', 'history'], true) ? $tab : 'active';

        $contracts = ($tab === 'history'
            ? $this->historyQuery($request->user(), $search)
            : $this->activeQuery($request->user(), $search))
            ->paginate(15)
            ->withQueryString();

        $payload = [
            'contracts' => $contracts,
            'search' => $search,
            'tab' => $tab,
        ];

        if ($this->wantsTablePartial($request)) {
            return view(
                $tab === 'history' ? 'kontrak-sewaan.partials.history-table' : 'kontrak-sewaan.partials.table',
                $payload,
            );
        }

        return view('kontrak-sewaan.index', $payload);
    }

    public function show(Request $request, RentalContract $contract): View
    {
        $this->ensureCanView($request, $contract);

        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser', 'parentContract.premise', 'documents.user']);
        app(SidebarNotificationService::class)->markContractAsViewed($request->user(), $contract);
        $proceedData = AdminProceedSteps::viewData($contract, 1);

        return view('kontrak-sewaan.show', array_merge($proceedData, [
            'contract' => $contract,
            'stepPanels' => AdminProceedSteps::stepPanelsFor($contract),
            'totalActiveSteps' => AdminProceedSteps::totalActiveSteps($contract),
        ]));
    }

    /**
     * @return Builder<RentalContract>
     */
    private function baseQuery(User $user, string $search): Builder
    {
        return RentalContract::query()
            ->hqApproved()
            ->notSuperseded()
            ->with([
                'premise',
                'submittedBy',
                'adminNegeriUser',
                'followUpApplications',
                'notificationViews' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->when(
                $user->isAdminNegeri() && filled($user->negeri),
                fn (Builder $query) => $query->whereHas('premise', fn (Builder $premiseQuery) => $premiseQuery->where('negeri', $user->negeri))
            )
            ->tap(fn (Builder $query) => RentalContractListSearch::apply($query, $search, [
                'include_admin_negeri' => true,
            ]));
    }

    /**
     * @return Builder<RentalContract>
     */
    private function activeQuery(User $user, string $search): Builder
    {
        $placeholder = RentalContract::PLACEHOLDER_CONTRACT_DATE;

        return $this->baseQuery($user, $search)
            ->kontrakNotExpired()
            ->orderByRaw(
                'CASE WHEN tarikh_mula = ? THEN COALESCE(sah_sehingga, \'9999-12-31\') ELSE COALESCE(tarikh_tamat, \'9999-12-31\') END ASC',
                [$placeholder]
            )
            ->orderBy('rental_contracts.id');
    }

    /**
     * @return Builder<RentalContract>
     */
    private function historyQuery(User $user, string $search): Builder
    {
        $placeholder = RentalContract::PLACEHOLDER_CONTRACT_DATE;

        return $this->baseQuery($user, $search)
            ->kontrakExpired()
            ->orderByRaw(
                'COALESCE(CASE WHEN tarikh_mula = ? THEN sah_sehingga ELSE tarikh_tamat END, tarikh_tamat) DESC',
                [$placeholder]
            )
            ->orderByDesc('rental_contracts.id');
    }

    private function ensureCanView(Request $request, RentalContract $contract): void
    {
        $user = $request->user();

        if (! $contract->isHqApproved()) {
            abort(404);
        }

        if ($user?->isAdminHq()) {
            return;
        }

        $contract->loadMissing('premise');

        if ($user?->isAdminNegeri() && filled($user->negeri) && $contract->premise?->negeri === $user->negeri) {
            return;
        }

        abort(403, 'Akses ditolak.');
    }
}
