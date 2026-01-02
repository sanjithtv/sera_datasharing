<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
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
     * List all roles
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('modules.roles.index', compact('roles'));
    }

    /**
     * Show all permissions for a given role (edit form)
     */
    public function editPermissions($roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        // Group permissions by module prefix
     $groupedPermissions = $permissions->groupBy(function ($perm) {
        return $perm->module ?? 'General';
    });

        return view('modules.roles.permissions', compact('role', 'groupedPermissions', 'rolePermissions'));
    }

    /**
     * Update permissions for a role
     */
    public function updatePermissions(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $selectedPermissions = $validated['permissions'] ?? [];

        $role->syncPermissions($selectedPermissions);

        return redirect()->route('security.roles.index')->with('success', "Permissions updated for role '{$role->name}'.");
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:roles,name,' . $id,
    ]);

    return response()->json([
            'success' => true,
            'role' => $request->ajax()
        ]);

    $role = Role::findOrFail($id);
    $role->update(['name' => $request->name]);

    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'role' => $role
        ]);
    }

    return redirect()->back()->with('success', 'Role updated successfully');
}
}
