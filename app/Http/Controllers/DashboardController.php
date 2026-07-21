<?php

namespace App\Http\Controllers;

use App\Models\RentalContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdminNegeri()) {
            return redirect()->route('status-permohonan.index');
        }

        if (! $user->isAdmin()) {
            return redirect()->route('login');
        }

        $permohonanBaharu = RentalContract::query()
            ->where('workflow_tahap', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ)
            ->count();

        $progressPermohonan = RentalContract::query()
            ->whereIn('workflow_tahap', RentalContract::draftAgreementWorkflows())
            ->count();

        $activeContracts = RentalContract::query()
            ->hqApproved()
            ->notSuperseded()
            ->with('premise')
            ->get();

        $kontrakAktif = $activeContracts->count();

        $kontrakLapanBulan = $activeContracts
            ->filter->isContractEndWithinEightMonths()
            ->count();

        $alertList = $activeContracts
            ->filter->isContractEndWithinEightMonths()
            ->sortBy(fn (RentalContract $contract) => $contract->daysUntilContractEnd())
            ->take(20)
            ->values();

        return view('dashboard.index', [
            'permohonanBaharu' => $permohonanBaharu,
            'progressPermohonan' => $progressPermohonan,
            'kontrakAktif' => $kontrakAktif,
            'kontrakLapanBulan' => $kontrakLapanBulan,
            'alertList' => $alertList,
        ]);
    }
}
