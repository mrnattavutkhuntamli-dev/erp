@extends('layouts.app')

@section('title', 'ข้อมูลส่วนตัว')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <h2 class="text-2xl font-bold text-slate-800">จัดการข้อมูลส่วนตัว</h2>

        <!-- ส่วนข้อมูลโปรไฟล์ -->
        <div class="p-6 bg-white shadow ring-1 ring-slate-200 sm:rounded-xl">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- ส่วนเปลี่ยนรหัสผ่าน -->
        <div class="p-6 bg-white shadow ring-1 ring-slate-200 sm:rounded-xl">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <!-- ส่วนความปลอดภัย (2FA) ✅ เพิ่มใหม่ -->
        <div class="p-6 bg-white shadow ring-1 ring-slate-200 sm:rounded-xl">
            <div class="max-w-xl">
                <h3 class="text-lg font-medium text-slate-900 mb-1">🔐 ความปลอดภัยขั้นสูง (2FA)</h3>
                <p class="text-sm text-slate-500 mb-4">เพิ่มความปลอดภัยให้บัญชีของคุณด้วยการสแกน QR Code และกรอกรหัส 6 หลักจากมือถือทุกครั้งที่ล็อกอิน</p>

                <a href="{{ route('two-factor.show') }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition shadow">
                    ตั้งค่าความปลอดภัย (2FA)
                </a>
            </div>
        </div>

        <!-- ส่วนลบบัญชี -->
        <div class="p-6 bg-white shadow ring-1 ring-slate-200 sm:rounded-xl">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
