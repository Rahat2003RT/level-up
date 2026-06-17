<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @param string $plan
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $plan): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasActiveSubscription($plan)) {
            return response()->json([
                'error' => 'Payment Required',
                'message' => "This feature requires an active [{$plan}] subscription plan."
            ], 402);
        }

        return $next($request);
    }
}
