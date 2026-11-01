<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * ใช้งาน:  permission:view-sales            (ต้องมีสิทธิ์นี้)
     *          permission:view-sales,manage-expenses  (มีอย่างใดอย่างหนึ่งก็ผ่าน)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = auth()->user();

        if (! $user || ! collect($permissions)->contains(fn ($p) => $user->can($p))) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}
