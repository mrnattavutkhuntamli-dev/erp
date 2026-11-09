<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // เปลี่ยนรหัสผ่านแล้ว ให้ออกจากระบบเครื่อง/เบราว์เซอร์อื่น (คง session ปัจจุบันไว้)
        \App\Support\Sessions::forgetFor($request->user()->id, $request->session()->getId());

        return back()->with('status', 'password-updated');
    }
}
