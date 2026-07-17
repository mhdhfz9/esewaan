<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        User $user,
        string $action,
        string $description,
        ?array $metadata = null,
        ?User $performedBy = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $user->id,
            'performed_by_user_id' => $performedBy?->id ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'created_at' => Carbon::now(),
        ]);
    }
}
