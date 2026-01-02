<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\SiteConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Carbon\Carbon;
use App\Models\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $maxAttempts = (int) SiteConfiguration::getValue('max_login_attempts', 5);
            $lockoutMins = (int) SiteConfiguration::getValue('lockout_minutes', 15);

            // Check lockout
            if ($user->lockout_until && $user->lockout_until->isFuture()) {
                $minutes = $user->lockout_until->diffInMinutes(now());
                return back()->withErrors(['email' => "Account locked. Try again in $minutes minutes."]);
            }

            if (Auth::attempt($validated)) {
                $user->update([
                    'login_attempts' => 0,
                    'lockout_until' => null,
                ]);

                return redirect()->intended('/');
            } else {
                $user->increment('login_attempts');

                if ($user->login_attempts >= $maxAttempts) {
                    $user->update([
                        'lockout_until' => Carbon::now()->addMinutes($lockoutMins),
                        'login_attempts' => 0,
                    ]);
                    return back()->withErrors(['email' => 'Too many failed attempts. Account locked for ' . $lockoutMins . ' minutes.']);
                }

                return back()->withErrors(['email' => 'Invalid credentials.']);
            }
        }

        return back()->withErrors(['email' => 'User not found.']);
    }
}
