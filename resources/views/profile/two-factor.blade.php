@extends('layouts.app')
@section('title', 'ตั้งค่าความปลอดภัย (2FA)')

@section('content')
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">🔐 ตั้งค่าความปลอดภัย (Two-Factor Authentication)</h1>

        @if (session('status'))
            <div class="bg-emerald-100 text-emerald-700 p-4 mb-4 rounded-lg border border-emerald-200">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 p-6">
            @if($user->google2fa_enabled)
                <!-- กรณีเปิดใช้งานอยู่ -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 mb-2">2FA เปิดใช้งานอยู่</h2>
                    <p class="text-slate-500 mb-6">บัญชีของคุณได้รับการปกป้องด้วย Two-Factor Authentication</p>
                    <form action="{{ route('two-factor.disable') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 shadow" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปิด 2FA?')">
                            ปิดใช้งาน 2FA
                        </button>
                    </form>
                </div>
            @else
                <!-- กรณีปิดใช้งาน (ให้สแกน QR) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 mb-4">1. สแกน QR Code</h2>
                        <div class="bg-slate-50 p-4 rounded-lg flex justify-center">
                            {!! $qrCodeSvg !!}
                        </div>
                        <p class="text-sm text-slate-500 mt-2">ใช้แอป Google Authenticator, Microsoft Authenticator หรือ Authy สแกน QR Code นี้</p>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 mb-4">2. กรอกรหัสยืนยัน</h2>
                        <p class="text-sm text-slate-600 mb-4">กรอกรหัส 6 หลักจากแอปเพื่อเปิดใช้งาน</p>

                        @if ($errors->any())
                            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded text-sm">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form action="{{ route('two-factor.enable') }}" method="POST">
                            @csrf
                            <input type="text" name="otp" required maxlength="6" placeholder="000000" class="w-full border p-3 rounded-lg text-center text-2xl tracking-[0.5em] font-bold mb-4 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 shadow">
                                เปิดใช้งาน 2FA
                            </button>
                        </form>

                        <div class="mt-4 text-xs text-slate-400">
                            <p>Secret Key (กรณีสแกน QR ไม่ได้):</p>
                            <code class="text-slate-600 break-all">{{ $user->google2fa_secret }}</code>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-6">
            <a href="{{ route('profile.edit') }}" class="text-blue-600 hover:underline">&larr; กลับไปหน้าโปรไฟล์</a>
        </div>
    </div>
@endsection
