<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ProfileUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

use App\Helpers\PasswordPolicy;

class ProfileUserController extends Controller
{
    public function __construct()
    {
        // Role/permission protection
        $this->middleware(['permission:read-licensee'])->only('index');
        $this->middleware(['permission:create-licensee'])->only(['create', 'store']);
        $this->middleware(['permission:edit-licensee'])->only(['edit', 'update']);
        $this->middleware(['permission:delete-licensee'])->only('archive');
    }

    /**
     * Display a listing of profile users
     */
    public function index()
    {
        $profileUsers = ProfileUser::where('status', '!=', 'archived')->paginate(10);
        return view('modules.profile_users.index', compact('profileUsers'));
    }

    /**
     * Show the form for creating a new profile user
     */
    public function create()
    {
        $roles = Role::pluck('name', 'id');
        return view('modules.profile_users.create', compact('roles'));
    }

    /**
     * Store a newly created profile user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname_en' => 'required|string|max:255',
            'fullname_ar' => 'nullable|string|max:255',
            'email'       => 'required|email|unique:sr_profile_users,email|unique:users,email',
            'phone'       => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
            'status'      => ['required', Rule::in(['active', 'inactive'])],
            'password'    => 'required|min:6|confirmed',
            'role_id'     => 'required|exists:roles,id',
        ]);

        if (!PasswordPolicy::validate($validated['password'])) {
                return back()->withErrors(['password' => 'Password does not meet current security policy.']);
        }

        // Create profile user
        $profileUser = ProfileUser::create([
            'fullname_en' => $validated['fullname_en'],
            'fullname_ar' => $validated['fullname_ar'] ?? null,
            'email'       => $validated['email'],
            'phone'       => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'status'      => $validated['status'],
        ]);

        // Create corresponding User
        $user = User::create([
            'name'            => $validated['fullname_en'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'profile_user_id' => $profileUser->id,
        ]);

        // Assign role
        $role = Role::findById($validated['role_id']);
        $user->assignRole($role);

        return redirect()->route('security.profile_users.index')->with('success', 'Profile user created successfully.');
    }

    /**
     * Show the form for editing the specified profile user
     */
    public function edit(Request $request,$profileUser_id)
    {
        $roles = Role::pluck('name', 'id');
        $profileUser = ProfileUser::find($profileUser_id);
        $user = $profileUser->user;
        return view('modules.profile_users.edit', compact('profileUser', 'user', 'roles'));
    }

     /**
     * Update the specified profile user
     */
    public function update(Request $request, $profileUser_id)
    {
        $profileUser = ProfileUser::find($profileUser_id);
        $validated = $request->validate([
            'fullname_en' => 'required|string|max:255',
            'fullname_ar' => 'nullable|string|max:255',
            'email' => [
    'nullable',
    'email',
    Rule::unique('sr_profile_users', 'email')->ignore($profileUser->id)->whereNotNull('email'),
    Rule::unique('users', 'email')->ignore(optional($profileUser->user)->id)->whereNotNull('email'),
],
            'phone'       => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
            'status'      => ['required', Rule::in(['active', 'inactive'])],
            'password'    => 'nullable|min:6|confirmed',
            'role_id'     => 'required|exists:roles,id',
        ]);

        if (!PasswordPolicy::validate($validated['password'])) {
                return back()->withErrors(['password' => 'Password does not meet current security policy.']);
        }

        $profileUser->update([
            'fullname_en' => $validated['fullname_en'],
            'fullname_ar' => $validated['fullname_ar'] ?? null,
            'email'       => $validated['email'],
            'phone'       => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'status'      => $validated['status'],
        ]);

        $user = $profileUser->user;

        
        if ($user) {
            $user->update([
                'name'  => $validated['fullname_en'],
                'email' => $validated['email'],
                'password' => isset($validated['password']) ? Hash::make($validated['password']) : $user->password,
            ]);

            // Update role
            $role = Role::findById($validated['role_id'], 'web');
            $user->syncRoles([$role]);
        }

        return redirect()->route('security.profile_users.index')->with('success', 'Profile user updated successfully.');
    }


    /**
     * Soft delete
     */
    public function destroy(ProfileUser $profileUser)
    {
        $profileUser->update(['status' => 'archived']);
        $profileUser->delete();

        if ($profileUser->user) {
            $profileUser->user->delete();
        }

        return redirect()->route('profile_users.index')->with('success', 'Profile user deleted successfully.');
    }



    ///PROFILE INFO
    public function profile()
{
    $user = auth()->user();
    $profileUser = $user->profileUser;

    return view('modules.profile_users.profile', compact('profileUser', 'user'));
}

public function profileEdit()
{
    $user = auth()->user();
    $profileUser = $user->profileUser;
    $roles = $user->roles; // optional to show role badge

    return view('modules.profile_users.edit_profile', compact('user', 'profileUser', 'roles'));
}

public function profileUpdate(Request $request)
{
    $user = auth()->user();
    $profileUser = $user->profileUser;

    $validated = $request->validate([
        'fullname_en' => 'required|string|max:255',
        'fullname_ar' => 'nullable|string|max:255',
        'phone'       => 'nullable|string|max:20',
        'designation' => 'nullable|string|max:100',
    ]);

    $profileUser->update($validated);
    $user->update(['name' => $validated['fullname_en']]);

    return back()->with('success', 'Profile updated successfully.');
}

public function changePassword(Request $request)
{
    $validated = $request->validate([
        'current_password' => 'required',
        'password' => 'required|confirmed|min:6',
    ]);

    if (!PasswordPolicy::validate($validated['password'])) {
                return back()->withErrors(['password' => 'Password does not meet current security policy.']);
        }

    $user = auth()->user();

    if (!\Hash::check($request->current_password, $user->password)) {
        return back()->with('error', 'Current password is incorrect.');
    }

    $user->update([
        'password' => \Hash::make($request->password)
    ]);

    return back()->with('success', 'Password changed successfully.');
}

    ///PROFILE INFO

  
}
