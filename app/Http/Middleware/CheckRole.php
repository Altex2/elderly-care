<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || !in_array($request->user()->role, explode('|', $role))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // Redirect based on user role
            $user = $request->user();
            if ($user->isCaregiver()) {
                return redirect()->route('caregiver.dashboard')
                    ->with('error', 'Nu aveți permisiunea de a accesa această zonă.');
            } else {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Nu aveți permisiunea de a accesa această zonă.');
            }
        }

        return $next($request);
    }
}
