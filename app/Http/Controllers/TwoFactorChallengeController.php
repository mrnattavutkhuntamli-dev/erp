<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    // หน้าแสดงฟอร์มกรอกรหัส 6 หลัก
    public function show()
    {
        // ถ้าไม่มีผู้ใช้ล็อกอิน หรือไม่ได้เปิด 2FA ให้เด้งกลับ
        if (!auth()->check() || !auth()->user()->google2fa_enabled) {
            return redirect()->route('dashboard');
        }

        return view('auth.two-factor-challenge');
    }

    // ตรวจสอบรหัส
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = auth()->user();
        $google2fa = new Google2FA();

        // ตรวจสอบรหัส
        if ($google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
            // บันทึก Session ว่ายืนยันแล้ว
            session(['2fa_verified' => true]);
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['otp' => 'รหัส OTP ไม่ถูกต้อง กรุณาลองใหม่']);
    }
}
