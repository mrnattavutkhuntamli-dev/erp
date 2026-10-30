<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorChallengeController extends Controller
{
    // หน้าแสดงฟอร์มกรอกรหัส 6 หลัก
    public function show()
    {
        // ถ้าไม่มีผู้ใช้ล็อกอิน หรือไม่ได้เปิด 2FA ให้เด้งกลับ
        if (! auth()->check() || ! auth()->user()->google2fa_enabled) {
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
        $key  = '2fa-verify:' . $user->id;

        // ✅ Rate Limit: 5 ครั้ง/นาที
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['otp' => "ลองผิดหลายครั้งเกินไป กรุณารอ {$seconds} วินาที"]);
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        // ✅ ตรวจสอบรหัส + กันใช้ซ้ำ
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            // ✅ กันใช้รหัสซ้ำ (เก็บ timestamp รหัสล่าสุด)
            $lastOtpTime = cache()->get('2fa-last-otp:' . $user->id);
            $now         = now()->timestamp;

            if ($lastOtpTime && ($now - $lastOtpTime) < 30) {
                RateLimiter::hit($key, 60);
                return back()->withErrors(['otp' => 'รหัสนี้ถูกใช้ไปแล้ว กรุณารอรหัสใหม่']);
            }

            cache()->put('2fa-last-otp:' . $user->id, $now, 60);
            RateLimiter::clear($key);
            session(['2fa_verified' => true]);
            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($key, 60);
        return back()->withErrors(['otp' => 'รหัส OTP ไม่ถูกต้อง']);
    }
}
