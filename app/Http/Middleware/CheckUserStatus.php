<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user && $user->blocked_at !== null) {

            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Your account is blocked.',
                'reason'  => $user->block_reason,
            ], 403);
        }

        return $next($request);
    }
}
