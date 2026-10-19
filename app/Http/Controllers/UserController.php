<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // 1. แสดงรายชื่อพนักงานทั้งหมด
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(15);
        return view('users.index', compact('users'));
    }

    // 2. หน้าเพิ่มพนักงานใหม่
    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    // 3. บันทึกพนักงานใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('success', 'เพิ่มพนักงานสำเร็จ!');
    }

    // 4. หน้าแก้ไขพนักงาน
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    // 5. อัปเดตข้อมูลพนักงาน
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // อัปเดตรหัสผ่านเฉพาะกรอกใหม่
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // อัปเดต Role
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'อัปเดตข้อมูลพนักงานสำเร็จ!');
    }

    // 6. ลบพนักงาน
    public function destroy(User $user)
    {
        // ป้องกันการลบตัวเอง
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถลบตัวเองได้');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'ลบพนักงานสำเร็จ!');
    }
}
