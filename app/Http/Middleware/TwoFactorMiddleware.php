<?php

namespace App\Http\Middleware;

// use Illuminate\Auth\Middleware\TwoFactorMiddleware as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Closure;

class TwoFactorMiddleware 
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        $stillValid = $user->{'2fa_verified_at'} &&
            $user->{'2fa_verified_at'}->gt(Carbon::now()->subDays(30));

        if (!$stillValid) {
            session()->forget('2fa_passed');
        }

        if (!session('2fa_passed')) {
            return redirect()->route('2fa.setup');
        }

        return $next($request);
    }
}