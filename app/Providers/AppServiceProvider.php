<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        if ($proxies = config('magical.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(180)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        // Brute-force protection: 5 attempts / minute per username+IP and 30 / minute per IP.
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('username')).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(40)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
    }
}
