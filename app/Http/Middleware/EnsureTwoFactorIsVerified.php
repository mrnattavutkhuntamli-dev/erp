<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // ถ้าผู้ใช้เปิด 2FA ไว้ และยังไม่ได้ยืนยันใน Session นี้
        if ($user && $user->google2fa_enabled && !session('2fa_verified')) {

            // อนุญาตให้เข้าถึงหน้า 2FA และการ logout ได้
            if ($request->routeIs('2fa.challenge') || $request->routeIs('2fa.verify') || $request->routeIs('logout')) {
                return $next($request);
            }

            // ถ้าไม่ใช่ ให้Redirect ไปหน้าล็อกอิน 2FA
            return redirect()->route('2fa.challenge');
        }

        return $next($request);
    }
}
