<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Enums\UserRole;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Carbon\Carbon;

#[Group('Пользователь / Elite', weight: 260)]
final class EliteController extends Controller
{
    /**
     * Приглашение в команду
     */
    public function storeInvitation(Request $request): JsonResponse
    {
        if ($request->user()->role !== UserRole::ELITE) {
            return response()->json(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        $invitation = TeamInvitation::create([
            'leader_id'  => $request->user()->id,
            'token'      => Str::random(32),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'token'      => $invitation->token,
                'expires_at' => $invitation->expires_at->toIso8601String(),
                'url'        => config('app.frontend_url') . '/join-team?token=' . $invitation->token
            ]
        ], 201);
    }
}
