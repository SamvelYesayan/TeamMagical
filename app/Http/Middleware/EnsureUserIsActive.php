<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user->loadMissing('role');

        // A disabled account loses access immediately, even with a still-valid session.
        if (! $user->isActive()) {
            $user->tokens()->delete();
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json(['message' => __('auth.disabled'), 'code' => 'account_disabled'], 401);
        }

        // A password change/reset elsewhere invalidates all other sessions of this user.
        if ($request->hasSession() && $request->session()->has('pw_stamp')
            && ! hash_equals($user->sessionStamp(), (string) $request->session()->get('pw_stamp'))) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'Սեսիան ավարտվել է։ Մուտք գործեք կրկին։', 'code' => 'session_expired'], 401);
        }

        // Temporary / reset passwords must be replaced before anything else can be done.
        if ($user->must_change_password && ! $request->routeIs('me.show', 'me.password', 'auth.logout')) {
            return response()->json([
                'message' => 'Խնդրում ենք նախ փոխել ժամանակավոր գաղտնաբառը։',
                'code' => 'password_change_required',
            ], 403);
        }

        return $next($request);
    }
}
