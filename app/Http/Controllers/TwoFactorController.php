<?php
namespace App\Http\Controllers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    // แสดงหน้าตั้งค่า 2FA (QR Code)
    public function show()
    {
        $user      = auth()->user();
        $google2fa = new Google2FA();

        // เปิด 2FA อยู่แล้ว: ไม่สร้าง/แสดง QR ของ secret เดิมซ้ำ
        if ($user->google2fa_enabled) {
            return view('profile.two-factor', ['qrCodeSvg' => null, 'user' => $user]);
        }

        // ถ้าผู้ใช้ยังไม่มี secret ให้สร้างใหม่
        if (empty($user->google2fa_secret)) {
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        // สร้าง URL สำหรับ QR Code
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        // สร้าง SVG QR Code
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer    = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return view('profile.two-factor', compact('qrCodeSvg', 'user'));
    }

    // เปิดใช้งาน 2FA (ยืนยันรหัส 6 หลัก)
    public function enable(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user      = auth()->user();
        $google2fa = new Google2FA();
        $key       = '2fa-enable:' . $user->id . '|' . $request->ip();

        // จำกัด 5 ครั้ง/นาที กันเดารหัส 6 หลัก
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['otp' => 'ลองผิดหลายครั้งเกินไป กรุณารอ ' . RateLimiter::availableIn($key) . ' วินาที']);
        }

        // ตรวจสอบรหัส OTP (ปฏิเสธรหัสที่เคยใช้แล้ว เหมือนหน้ายืนยันตอนล็อกอิน)
        $ts = $google2fa->verifyKeyNewer(
            $user->google2fa_secret, $request->otp, cache()->get('2fa-last-ts:' . $user->id)
        );

        if ($ts !== false) {
            RateLimiter::clear($key);
            cache()->put('2fa-last-ts:' . $user->id, $ts, now()->addMinutes(5));
            $user->google2fa_enabled = true;
            $user->save();
            session(['2fa_verified' => true]);

            return redirect()->route('profile.edit')->with('status', 'เปิดใช้งาน 2FA สำเร็จ!');
        }

        RateLimiter::hit($key, 60);

        return back()->withErrors(['otp' => 'รหัส OTP ไม่ถูกต้อง กรุณาลองใหม่']);
    }

    // ปิดใช้งาน 2FA
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'otp'      => 'required|digits:6',
        ]);

        $user = auth()->user();

        // ตรวจสอบรหัสผ่านก่อนปิด 2FA
        if (! \Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'รหัสผ่านไม่ถูกต้อง']);
        }

        // ต้องยืนยัน OTP ด้วย (จำกัด 5 ครั้ง/นาที และปฏิเสธรหัสที่เคยใช้แล้ว)
        $google2fa = new Google2FA();
        $key       = '2fa-disable:' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['otp' => 'ลองผิดหลายครั้งเกินไป กรุณารอ ' . RateLimiter::availableIn($key) . ' วินาที']);
        }

        $ts = $google2fa->verifyKeyNewer(
            $user->google2fa_secret, $request->otp, cache()->get('2fa-last-ts:' . $user->id)
        );

        if ($ts === false) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['otp' => 'รหัส OTP ไม่ถูกต้อง หรือเพิ่งถูกใช้ไปแล้ว (ถ้าเพิ่งล็อกอิน กรุณารอรหัสถัดไปประมาณ 30 วินาที)']);
        }

        RateLimiter::clear($key);
        cache()->put('2fa-last-ts:' . $user->id, $ts, now()->addMinutes(5));

        $user->google2fa_enabled = false;
        $user->google2fa_secret  = null;
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'ปิดใช้งาน 2FA สำเร็จ!');
    }
}
