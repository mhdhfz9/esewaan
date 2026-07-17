<?php

namespace App\Http\Controllers;

use App\Models\Premise;
use App\Models\RentalContract;
use App\Support\BuildingTypes;
use Carbon\Carbon;
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

        if ($user->isAdmin()) {
            $negeri = $request->input('negeri');
            $jenisBangunan = $request->input('jenis_bangunan');

            $contractsQuery = RentalContract::query()
                ->with('premise')
                ->when($negeri, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('negeri', $negeri)))
                ->when($jenisBangunan, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('jenis_bangunan', $jenisBangunan)));

            $totalPremises = Premise::query()
                ->when($negeri, fn ($q) => $q->where('negeri', $negeri))
                ->when($jenisBangunan, fn ($q) => $q->where('jenis_bangunan', $jenisBangunan))
                ->count();

            $amaranLapanBulan = (clone $contractsQuery)
                ->where('status_aktif', 'aktif')
                ->where('tarikh_tamat', '>=', Carbon::today())
                ->whereRaw('DATEDIFF(tarikh_tamat, CURDATE()) <= 240')
                ->count();

            $tamatTempoh = (clone $contractsQuery)
                ->where('status_aktif', 'tamat_tempoh')
                ->count();

            $jumlahKutipanBulanan = (clone $contractsQuery)
                ->where('status_aktif', 'aktif')
                ->where('tarikh_tamat', '>=', Carbon::today())
                ->sum('kadar_sewa_bulanan');

            $statusCounts = (clone $contractsQuery)
                ->selectRaw('status_aktif, count(*) as jumlah')
                ->groupBy('status_aktif')
                ->pluck('jumlah', 'status_aktif');

            $byNegeri = Premise::query()
                ->when($jenisBangunan, fn ($q) => $q->where('jenis_bangunan', $jenisBangunan))
                ->selectRaw('negeri, count(*) as jumlah')
                ->groupBy('negeri')
                ->orderByDesc('jumlah')
                ->get();

            $peringkatCounts = (clone $contractsQuery)
                ->whereNotNull('peringkat_proses')
                ->where('peringkat_proses', '!=', '')
                ->selectRaw('peringkat_proses, count(*) as jumlah')
                ->groupBy('peringkat_proses')
                ->orderByDesc('jumlah')
                ->limit(6)
                ->get();

            $alertList = RentalContract::query()
                ->with('premise')
                ->when($negeri, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('negeri', $negeri)))
                ->when($jenisBangunan, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('jenis_bangunan', $jenisBangunan)))
                ->where('status_aktif', 'aktif')
                ->where('tarikh_tamat', '>=', Carbon::today())
                ->whereRaw('DATEDIFF(tarikh_tamat, CURDATE()) <= 240')
                ->orderBy('tarikh_tamat')
                ->limit(20)
                ->get();

            $recentContracts = RentalContract::query()
                ->with('premise')
                ->when($negeri, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('negeri', $negeri)))
                ->when($jenisBangunan, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('jenis_bangunan', $jenisBangunan)))
                ->latest()
                ->limit(10)
                ->get();

            $negeriList = Premise::query()->distinct()->pluck('negeri', 'negeri')->sort()->filter();
            $jenisList = BuildingTypes::all();

            return view('dashboard.index', [
                'totalPremises' => $totalPremises,
                'amaranLapanBulan' => $amaranLapanBulan,
                'tamatTempoh' => $tamatTempoh,
                'jumlahKutipanBulanan' => $jumlahKutipanBulanan,
                'statusCounts' => $statusCounts,
                'byNegeri' => $byNegeri,
                'peringkatCounts' => $peringkatCounts,
                'alertList' => $alertList,
                'recentContracts' => $recentContracts,
                'negeriList' => $negeriList,
                'jenisList' => $jenisList,
                'filterNegeri' => $negeri,
                'filterJenis' => $jenisBangunan,
            ]);
        }

        return redirect()->route('login');
    }
}
