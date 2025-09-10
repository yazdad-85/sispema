<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Institution;

class UserManagementController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $users = User::with('institutions')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $institutions = Institution::all();
        return view('users.create', compact('institutions'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:super_admin,staff,kasir',
            'phone' => 'nullable|string|max:20',
            'institution_ids' => 'array',
            'institution_ids.*' => 'exists:institutions,id'
        ]);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
        ]);

        // Attach institutions if provided
        if ($request->has('institution_ids')) {
            $newUser->institutions()->attach($request->institution_ids);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat');
    }

    public function edit(User $user)
    {
        $auth = Auth::user();
        if (!$auth || !$auth->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $institutions = Institution::all();
        $assigned = $user->institutions()->pluck('institutions.id')->toArray();
        return view('users.edit', compact('user', 'institutions', 'assigned'));
    }

    public function update(Request $request, User $user)
    {
        $auth = Auth::user();
        if (!$auth || !$auth->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:super_admin,staff,kasir',
            'phone' => 'nullable|string|max:20',
            'institution_ids' => 'array',
            'institution_ids.*' => 'exists:institutions,id'
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phone,
        ];

        // Update password only if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update institution access
        $institutionIds = $request->input('institution_ids', []);
        $validatedIds = Institution::whereIn('id', $institutionIds)->pluck('id')->toArray();
        $user->institutions()->sync($validatedIds);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        $auth = Auth::user();
        if (!$auth || !$auth->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        // Prevent deleting self
        if ($user->id === $auth->id) {
            return redirect()->route('users.index')->with('error', 'Tidak dapat menghapus akun sendiri');
        }

        $user->institutions()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus');
    }
}
