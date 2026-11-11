<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $key  = '2fa-verify:' . $user->id . '|' . $request->ip(); // ผูก IP ด้วย กันคนอื่นกดผิดรัว ๆ เพื่อล็อกเจ้าของบัญชี

        // ✅ Rate Limit: 5 ครั้ง/นาที
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['otp' => "ลองผิดหลายครั้งเกินไป กรุณารอ {$seconds} วินาที"]);
        }

        // secret ถอดรหัสไม่ได้ (เช่น APP_KEY เปลี่ยน) → แจ้งผู้ใช้ชัดเจนแทน 500 และไม่นับเป็นความพยายามผิด
        $secret = $user->twoFactorSecretOrNull();
        if ($secret === null) {
            Log::warning('2FA secret ถอดรหัสไม่ได้หรือว่างเปล่า (ตรวจสอบว่า APP_KEY ถูกเปลี่ยนหรือไม่)', ['user_id' => $user->id]);
            return back()->withErrors(['otp' => 'ข้อมูล 2FA ของบัญชีนี้อ่านไม่ได้ (อาจเกิดจากการเปลี่ยนค่าความปลอดภัยของระบบ) กรุณาติดต่อผู้ดูแลระบบเพื่อรีเซ็ต 2FA']);
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        // verifyKeyNewer: ปฏิเสธรหัสที่เคยใช้แล้ว (ตาม timestamp ของรหัสล่าสุด)
        $lastTs = cache()->get('2fa-last-ts:' . $user->id);
        $ts     = $google2fa->verifyKeyNewer($secret, $request->otp, $lastTs);

        if ($ts !== false) {
            cache()->put('2fa-last-ts:' . $user->id, $ts, now()->addMinutes(5));
            RateLimiter::clear($key);
            $request->session()->regenerate(); // กัน session fixation หลังยืนยันตัวตนขั้นที่ 2
            session(['2fa_verified' => true]);
            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($key, 60);
        return back()->withErrors(['otp' => 'รหัส OTP ไม่ถูกต้องหรือถูกใช้ไปแล้ว']);
    }
}
