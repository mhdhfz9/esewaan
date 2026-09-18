<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, SidebarNotificationService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $counts = $service->forUser($user);

        return response()->json([
            'list_menu' => $user->isAdminHq()
                ? max($counts['status_permohonan'], $counts['hq_pending_withdrawal'])
                : $counts['status_permohonan'],
            'kontrak_sewaan' => $counts['kontrak_sewaan'],
            'inbox_total' => $counts['inbox_total'],
            'inbox_items' => $user->isAdmin()
                ? $service->inboxItemsPayload($user, 8)
                : [],
        ]);
    }
}
