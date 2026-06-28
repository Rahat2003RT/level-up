<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();
        if ($user && $user->blocked_at !== null) {
            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $token = $user->currentAccessToken();
                if ($token instanceof PersonalAccessToken) {
                    $token->delete();
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Your account is blocked.',
                'reason' => $user->block_reason,
            ], 403);
        }

        return $next($request);
    }
}
