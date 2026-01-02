<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\SiteConfiguration;
use Carbon\Carbon;

class EnforceSecuritySettings
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        // Session timeout
        $timeout = (int) SiteConfiguration::getValue('session_timeout', 30);
        $lastActivity = session('last_activity');
        if ($lastActivity && now()->diffInMinutes($lastActivity) >= $timeout) {
            Auth::logout();
            session()->invalidate();
            return redirect()->route('login')->with('error', 'Session expired due to inactivity.');
        }
        session(['last_activity' => now()]);

        // Password expiry
        $expiryDays = (int) SiteConfiguration::getValue('password_expiry_days', 90);
        if ($expiryDays > 0 && $user->password_changed_at) {
            $expired = Carbon::parse($user->password_changed_at)->addDays($expiryDays)->isPast();
            if ($expired) {
                Auth::logout();
                return redirect()->route('password.change')->with('error', 'Your password has expired. Please set a new password.');
            }
        }

        return $next($request);
    }
}
