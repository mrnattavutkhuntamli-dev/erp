<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // remember_token ไม่อยู่ใน $fillable จึงใช้ forceFill; หมุน token เพื่อให้ cookie "จำฉัน" เดิมใช้ไม่ได้
        $request->user()->forceFill([
            'password'       => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // เปลี่ยนรหัสผ่านแล้ว ให้ออกจากระบบเครื่อง/เบราว์เซอร์อื่น (คง session ปัจจุบันไว้)
        \App\Support\Sessions::forgetFor($request->user()->id, $request->session()->getId());

        return back()->with('status', 'password-updated');
    }
}
