<?php

namespace App\Http\Controllers\Cabinet\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'site'])->paginate(15);
        return view('cabinet.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $sites = Site::all();
        return view('cabinet.users.create', compact('roles', 'sites'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
            'site_id' => 'nullable|exists:sites,id',
            'is_active' => 'boolean'
        ]);
        
        $data['password'] = Hash::make($data['password']);
        
        User::create($data);
        
        return redirect()->route('cabinet.users.index')
            ->with('success', 'Пользователь создан');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $sites = Site::all();
        return view('cabinet.users.edit', compact('user', 'roles', 'sites'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'role_id' => 'sometimes|exists:roles,id',
            'site_id' => 'nullable|exists:sites,id',
            'is_active' => 'boolean'
        ]);
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);
        
        return redirect()->route('cabinet.users.index')
            ->with('success', 'Пользователь обновлен');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Нельзя удалить себя');
        }
        
        $user->delete();
        
        return redirect()->route('cabinet.users.index')
            ->with('success', 'Пользователь удален');
    }
}
