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

        // กันเปลี่ยน role ของ Admin คนสุดท้าย — ต้องเช็กก่อนบันทึกข้อมูลใดๆ
        // (เดิมบันทึกชื่อ/อีเมล/รหัสผ่านไปก่อน แล้วค่อยแจ้ง error ทำให้ข้อมูลเปลี่ยนทั้งที่แจ้งว่าไม่สำเร็จ)
        if ($user->hasRole('admin') && $validated['role'] !== 'admin' && $this->wouldLeaveNoActiveAdmin($user)) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถเปลี่ยน role ของ Admin คนสุดท้ายได้');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // อัปเดตรหัสผ่านเฉพาะกรอกใหม่
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $validated) {
            $user->save();

            // อัปเดต Role
            $user->syncRoles([$validated['role']]);

            return redirect()->route('users.index')->with('success', 'อัปเดตข้อมูลพนักงานสำเร็จ!');
        });
    }

    // 6. เปิด/ปิดการใช้งานบัญชี (ใช้กับพนักงานที่ลาออกแต่มีประวัติบิล/กะ จึงลบไม่ได้)
    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถปิดใช้งานบัญชีของตัวเองได้');
        }

        if ($user->is_active && $user->hasRole('admin')
            && User::role('admin')->where('is_active', true)->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถปิดใช้งาน Admin คนสุดท้ายได้');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        // ปิดใช้งาน: ล้าง session เดิมของผู้ใช้ (กรณีใช้ session แบบ database)
        if (! $user->is_active && config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }

        return redirect()->route('users.index')->with(
            'success',
            $user->is_active ? 'เปิดใช้งานบัญชี ' . $user->name . ' แล้ว' : 'ปิดใช้งานบัญชี ' . $user->name . ' แล้ว'
        );
    }

    /**
     * ถ้าเอา $user ออกจากการเป็น admin ที่ใช้งานได้ (ลบ/ลด role) แล้วจะไม่เหลือ admin ที่ active เลยหรือไม่
     * (admin ที่ถูกปิดใช้งานเข้าสู่ระบบไม่ได้ จึงไม่นับ)
     */
    private function wouldLeaveNoActiveAdmin(User $user): bool
    {
        if (! $user->is_active) {
            return false; // คนนี้ไม่ใช่ admin ที่ใช้งานอยู่แล้ว การเอาออกไม่กระทบจำนวน admin ที่ใช้งานได้
        }

        return User::role('admin')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist();
    }

    // 7. ลบพนักงาน
    public function destroy(User $user)
    {
        // ป้องกันการลบตัวเอง
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถลบตัวเองได้');
        }

        if ($user->hasRole('admin') && $this->wouldLeaveNoActiveAdmin($user)) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถลบ Admin คนสุดท้ายได้');
        }

        if (\App\Models\Sale::where('user_id', $user->id)->exists()
            || \App\Models\CashierShift::where('user_id', $user->id)->exists()) {
            return redirect()->route('users.index')->with('error', 'พนักงานคนนี้มีประวัติการใช้งานแล้ว ไม่สามารถลบได้');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'ลบพนักงานสำเร็จ!');
    }
}
