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
        $contracts = $this->contractsQuery($request->user(), $search)
            ->paginate(15)
            ->withQueryString();

        $payload = [
            'contracts' => $contracts,
            'search' => $search,
        ];

        if ($this->wantsTablePartial($request)) {
            return view('kontrak-sewaan.partials.table', $payload);
        }

        return view('kontrak-sewaan.index', $payload);
    }

    public function show(Request $request, RentalContract $contract): View
    {
        $this->ensureCanView($request, $contract);

        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser', 'parentContract.premise']);
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
    private function contractsQuery(User $user, string $search): Builder
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
            ]))
            ->orderForUserList($user, 'hq_approved_at');
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
