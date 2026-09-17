<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithTablePartial;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\ContractDocument;
use App\Models\ProcessLog;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ListSearch;
use App\Support\MalaysianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserController extends Controller
{
    use RespondsWithTablePartial;

    public function index(Request $request): View
    {
        $admin = auth()->user();
        $search = trim((string) $request->input('search', ''));
        $sort = $this->resolveSortColumn((string) $request->input('sort', 'created_at'));
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $baseQuery = $this->usersQuery($admin, $search, $sort, $direction);

        $adminUsers = (clone $baseQuery)
            ->where('role', 'admin_hq')
            ->get();

        $negeriUsers = (clone $baseQuery)
            ->where('role', 'admin_negeri')
            ->get();

        $payload = [
            'adminUsers' => $adminUsers,
            'negeriUsers' => $negeriUsers,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'canManageUserStatus' => $admin->isAdminHq(),
            'showAdminSection' => $admin->isAdminHq(),
        ];

        if ($this->wantsTablePartial($request)) {
            return view('users.partials.table', $payload);
        }

        return view('users.index', $payload);
    }

    public function edit(User $user): View
    {
        $this->ensureUserVisible($user);

        $admin = auth()->user();

        return view('users.edit', [
            'user' => $user,
            'negeriList' => MalaysianStates::all(),
            'lockNegeri' => $admin->isAdminNegeri(),
            'lockedNegeri' => $admin->negeri,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureUserVisible($user);

        $data = $request->validated();
        $passwordChanged = filled($data['password'] ?? null);
        $previousRole = $user->role;
        $newRole = $request->resolvedRole();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'negeri' => $request->resolvedNegeri(),
            'role' => $newRole,
            ...($passwordChanged ? ['password' => $data['password']] : []),
        ]);

        ActivityLogger::log(
            $user,
            'profile_updated',
            'Profil dikemaskini oleh pentadbir.',
            performedBy: $request->user(),
        );

        if ($passwordChanged) {
            ActivityLogger::log(
                $user,
                'password_reset',
                'Kata laluan ditetapkan semula oleh pentadbir.',
                performedBy: $request->user(),
            );
        }

        if ($previousRole !== $newRole) {
            ActivityLogger::log(
                $user,
                'role_updated',
                'Peranan ditukar daripada '.$this->roleLabel($previousRole).' kepada '.$this->roleLabel($newRole).'.',
                ['previous_role' => $previousRole, 'new_role' => $newRole],
                $request->user(),
            );
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Profil '.$user->name.' berjaya dikemaskini.');
    }

    public function auditTrail(User $user): View
    {
        $this->ensureUserVisible($user);

        return view('users.audit-trail', [
            'user' => $user,
            'activities' => $this->buildAuditTimeline($user),
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $admin = auth()->user();

        if (! $admin?->isAdminHq()) {
            abort(403, 'Akses ditolak.');
        }

        $this->ensureUserVisible($user);

        if ($user->is($admin)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Anda tidak boleh mengubah status akaun sendiri.');
        }

        if ($user->is_active && $user->isAdminHq()) {
            $remainingActiveAdmins = User::query()
                ->where('role', 'admin_hq')
                ->where('is_active', true)
                ->whereKeyNot($user->getKey())
                ->exists();

            if (! $remainingActiveAdmins) {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'Sekurang-kurangnya satu Ibu Pejabat aktif mesti kekal dalam sistem.');
            }
        }

        $wasActive = $user->is_active;
        $user->update(['is_active' => ! $wasActive]);

        ActivityLogger::log(
            $user,
            $wasActive ? 'account_deactivated' : 'account_activated',
            $wasActive
                ? 'Akaun pengguna dinyahaktifkan oleh Ibu Pejabat.'
                : 'Akaun pengguna diaktifkan semula oleh Ibu Pejabat.',
            performedBy: $admin,
        );

        $message = $wasActive
            ? 'Akaun '.$user->name.' telah dinyahaktifkan.'
            : 'Akaun '.$user->name.' telah diaktifkan.';

        return redirect()
            ->route('users.index')
            ->with('success', $message);
    }

    /**
     * @return Collection<int, array{occurred_at: \Illuminate\Support\Carbon, title: string, description: string, performed_by: string|null, source: string}>
     */
    private function buildAuditTimeline(User $user): Collection
    {
        $items = collect();

        ActivityLog::query()
            ->where('user_id', $user->id)
            ->with('performedBy')
            ->orderByDesc('created_at')
            ->get()
            ->each(function (ActivityLog $log) use ($items): void {
                $items->push([
                    'occurred_at' => $this->normalizeOccurredAt($log->created_at),
                    'title' => $this->actionLabel($log->action),
                    'description' => $log->description,
                    'performed_by' => $log->performedBy?->name,
                    'source' => 'Log Sistem',
                ]);
            });

        ProcessLog::query()
            ->where('user_id', $user->id)
            ->with('rentalContract.premise')
            ->get()
            ->each(function (ProcessLog $log) use ($items): void {
                $items->push([
                    'occurred_at' => $this->normalizeOccurredAt($log->created_at ?? $log->tarikh_tindakan),
                    'title' => $log->jenis_tindakan,
                    'description' => $log->keterangan ?? 'Kemaskini proses kontrak: '.($log->rentalContract?->premise?->nama_ptj ?? '–'),
                    'performed_by' => null,
                    'source' => 'Proses Kontrak',
                ]);
            });

        RentalContract::query()
            ->where('submitted_by_user_id', $user->id)
            ->with('premise')
            ->get()
            ->each(function (RentalContract $contract) use ($items): void {
                $items->push([
                    'occurred_at' => $this->normalizeOccurredAt($contract->created_at),
                    'title' => 'Hantar permohonan sewaan',
                    'description' => 'Premis: '.($contract->premise?->nama_ptj ?? '–').' · Status: '.$contract->status_aktif,
                    'performed_by' => null,
                    'source' => 'Permohonan',
                ]);
            });

        ContractDocument::query()
            ->where('user_id', $user->id)
            ->with('rentalContract.premise')
            ->get()
            ->each(function (ContractDocument $document) use ($items): void {
                $items->push([
                    'occurred_at' => $this->normalizeOccurredAt($document->created_at),
                    'title' => 'Muat naik dokumen',
                    'description' => ($document->nama_fail ?? 'Dokumen').' ('.$document->jenis.') · '.($document->rentalContract?->premise?->nama_ptj ?? '–'),
                    'performed_by' => null,
                    'source' => 'Dokumen',
                ]);
            });

        return $items
            ->filter(fn (array $item) => $item['occurred_at'] instanceof Carbon)
            ->sortByDesc(fn (array $item) => $item['occurred_at'])
            ->values();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    private function usersQuery(User $admin, string $search, string $sort, string $direction): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->visibleToAdmin($admin)
            ->when($search !== '', function ($query) use ($search): void {
                $like = ListSearch::like($search);
                $needle = mb_strtolower($search);

                $query->where(function ($query) use ($like, $needle): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('negeri', 'like', $like)
                        ->orWhere('role', 'like', $like);

                    $roleMatches = ListSearch::matchingKeys([
                        'admin_hq' => 'Ibu Pejabat',
                        'admin_negeri' => 'Negeri',
                    ], $needle);

                    if ($roleMatches !== []) {
                        $query->orWhereIn('role', $roleMatches);
                    }

                    $statusMatches = ListSearch::matchingKeys([
                        1 => 'Aktif',
                        0 => 'Nyahaktif',
                    ], $needle);

                    if ($statusMatches !== []) {
                        $query->orWhereIn('is_active', $statusMatches);
                    }
                });
            })
            ->orderBy($sort, $direction);
    }

    private function resolveSortColumn(string $sort): string
    {
        return in_array($sort, ['name', 'email', 'role', 'negeri', 'is_active', 'created_at'], true)
            ? $sort
            : 'created_at';
    }

    private function ensureUserVisible(User $user): void
    {
        $admin = auth()->user();

        if (! $admin || ! $user->isVisibleToAdmin($admin)) {
            abort(403, 'Akses ditolak.');
        }
    }

    private function normalizeOccurredAt(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse($value);
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'login' => 'Log masuk',
            'logout' => 'Log keluar',
            'profile_updated' => 'Kemaskini profil',
            'password_reset' => 'Reset kata laluan',
            'role_updated' => 'Ubah peranan',
            'account_activated' => 'Aktifkan akaun',
            'account_deactivated' => 'Nyahaktif akaun',
            'user_created' => 'Akaun didaftarkan',
            'application_submitted' => 'Hantar permohonan',
            'document_uploaded' => 'Muat naik dokumen',
            'contract_status_updated' => 'Kemaskini status kontrak',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin_hq' => 'Ibu Pejabat',
            'admin_negeri' => 'Negeri',
            default => 'Tidak diketahui',
        };
    }
}
