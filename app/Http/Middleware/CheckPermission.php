<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // ถ้าผู้ใช้ไม่มีสิทธิ์นี้ ให้บล็อกและแจ้งเตือน
        if (!auth()->user() || !auth()->user()->can($permission)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}
