<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User, 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'company' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'company' => $validated['company'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'is_active' => true,
        ]);
        $user->assignRole($validated['role']);
        DashboardPreference::firstOrCreate(['user_id' => $user->id], ['theme' => 'dark']);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        if ($request->input('password') === '') {
            $request->merge(['password' => null]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'company' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }
}
