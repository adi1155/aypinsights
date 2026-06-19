<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** @var array<string, array<int, string>> */
    protected array $permissionGroups = [
        'Dashboard Access' => [
            'view daily closing',
            'view ap dashboard',
            'view ar dashboard',
            'view expense dashboard',
            'view payroll dashboard',
            'view attendance dashboard',
            'view production dashboard',
        ],
        'Administration' => [
            'manage users',
            'manage roles',
            'manage companies',
            'view audit logs',
        ],
        'Reports' => [
            'export reports',
        ],
    ];

    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $permissionGroups = $this->permissionGroups;

        return view('admin.roles.index', compact('roles', 'permissionGroups'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role,
            'permissionGroups' => $this->permissionGroups,
            'assignedPermissions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups,
            'assignedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to users.');
        }

        if (in_array($role->name, ['CEO', 'CFO'], true)) {
            return back()->with('error', 'Core executive roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
