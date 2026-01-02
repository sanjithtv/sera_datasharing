<?php

// app/Http/Controllers/Auth/PasswordChangeController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\PasswordPolicy;

class PasswordChangeController extends Controller
{
    public function showForm()
    {
        return view('auth.passwords.change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Incorrect current password.']);
        }

        if (!PasswordPolicy::validate($request->password)) {
            return back()->withErrors(['password' => 'Password does not meet security policy.']);
        }

        Auth::user()->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Password updated successfully.');
    }
}
