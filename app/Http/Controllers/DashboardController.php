<?php

namespace App\Http\Controllers;

use App\Models\RentalContract;
use App\Support\StatusTindakan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $dalamTindakanByStatus = collect(StatusTindakan::breakdownWithCounts());
        $progressPermohonan = (int) $dalamTindakanByStatus->sum('count');

        $activeContracts = RentalContract::query()
            ->hqApproved()
            ->notSuperseded()
            ->kontrakNotExpired()
            ->with('premise')
            ->get();

        $kontrakAktif = $activeContracts->count();

        $kontrakTigaBulanList = $activeContracts
            ->filter->isContractEndWithinThreeMonths()
            ->sortBy(fn (RentalContract $contract) => $contract->daysUntilContractEnd())
            ->values();

        $kontrakLapanBulanList = $activeContracts
            ->filter(fn (RentalContract $contract): bool => $contract->isContractEndWithinEightMonths()
                && ! $contract->isContractEndWithinThreeMonths())
            ->sortBy(fn (RentalContract $contract) => $contract->daysUntilContractEnd())
            ->values();

        $kontrakTigaBulan = $kontrakTigaBulanList->count();
        $kontrakLapanBulan = $kontrakLapanBulanList->count();
        $kontrakLebihLapanBulan = max(0, $kontrakAktif - $kontrakTigaBulan - $kontrakLapanBulan);

        return view('dashboard.index', [
            'permohonanBaharu' => $permohonanBaharu,
            'progressPermohonan' => $progressPermohonan,
            'dalamTindakanByStatus' => $dalamTindakanByStatus,
            'kontrakAktif' => $kontrakAktif,
            'kontrakTigaBulan' => $kontrakTigaBulan,
            'kontrakLapanBulan' => $kontrakLapanBulan,
            'kontrakLebihLapanBulan' => $kontrakLebihLapanBulan,
            'alertListTigaBulan' => $kontrakTigaBulanList->take(20),
            'alertListLapanBulan' => $kontrakLapanBulanList->take(20),
            'kontrakClassChart' => $this->kontrakClassChartData(
                $kontrakLebihLapanBulan,
                $kontrakLapanBulan,
                $kontrakTigaBulan,
            ),
            'statusChart' => $this->statusChartData($dalamTindakanByStatus),
        ]);
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int}
     */
    private function kontrakClassChartData(int $lebihLapan, int $lapan, int $tiga): array
    {
        return [
            'labels' => [
                'Lebih 8 bulan',
                'Bawah 8 bulan',
                'Bawah 3 bulan',
            ],
            'values' => [$lebihLapan, $lapan, $tiga],
            'colors' => ['#059669', '#d97706', '#dc2626'],
            'total' => $lebihLapan + $lapan + $tiga,
        ];
    }

    /**
     * @param  Collection<int, array{key: string, status: string, count: int}>  $rows
     * @return array{labels: list<string>, values: list<int>, max: int}
     */
    private function statusChartData(Collection $rows): array
    {
        $withCounts = $rows
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values();

        if ($withCounts->isEmpty()) {
            return [
                'labels' => [],
                'values' => [],
                'max' => 1,
            ];
        }

        $values = $withCounts->pluck('count')->map(fn ($count): int => (int) $count)->all();

        return [
            'labels' => $withCounts->pluck('status')->all(),
            'values' => $values,
            'max' => max($values),
        ];
    }
}
