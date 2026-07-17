<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'negeri',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin_hq', 'admin_negeri'], true);
    }

    public function isAdminHq(): bool
    {
        return $this->role === 'admin_hq';
    }

    public function isAdminNegeri(): bool
    {
        return $this->role === 'admin_negeri';
    }

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    public function statusLabel(): string
    {
        return $this->isActive() ? 'Aktif' : 'Nyahaktif';
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin_hq' => 'Admin',
            'admin_negeri' => 'Negeri',
            default => 'Tidak diketahui',
        };
    }

    public function homeRoute(): string
    {
        if ($this->isAdminHq()) {
            return route('dashboard');
        }

        if ($this->isAdminNegeri()) {
            return route('status-permohonan.index');
        }

        return route('dashboard');
    }

    public function isVisibleToAdmin(User $admin): bool
    {
        if ($admin->isAdminHq()) {
            return true;
        }

        if ($admin->isAdminNegeri()) {
            return filled($admin->negeri)
                && $this->negeri === $admin->negeri
                && $this->role === 'admin_negeri';
        }

        return false;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeVisibleToAdmin(Builder $query, User $admin): Builder
    {
        if ($admin->isAdminNegeri() && filled($admin->negeri)) {
            return $query
                ->where('negeri', $admin->negeri)
                ->where('role', 'admin_negeri');
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<RentalContract, $this>
     */
    public function rentalContracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RentalContract::class, 'submitted_by_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ActivityLog, $this>
     */
    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
