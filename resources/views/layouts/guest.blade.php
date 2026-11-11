<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $companyName = rescue(fn () => \App\Models\Setting::get('company_name', 'Mini ERP'), 'Mini ERP');
    @endphp

    <title>เข้าสู่ระบบ | {{ $companyName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-thai:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'IBM Plex Sans Thai', ui-sans-serif, system-ui, sans-serif; }
        /* กันกรณี Tailwind ยังไม่ได้ build: บังคับให้การ์ดอยู่กลางและไม่กว้างเต็มจอ */
        .login-wrap { width: 100%; max-width: 26rem; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body class="antialiased text-slate-900 bg-slate-100">
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="login-wrap">

        {{-- โลโก้ / ชื่อบริษัท --}}
        <a href="/" class="mb-6 flex items-center justify-center gap-2 text-indigo-700">
            <span class="text-3xl">🚀</span>
            <span class="text-2xl font-bold">{{ $companyName }}</span>
        </a>

        {{-- การ์ด login --}}
        <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-slate-200">
            <h1 class="text-xl font-bold text-center">เข้าสู่ระบบ</h1>
            <p class="mt-1 text-center text-sm text-slate-500">กรอกอีเมลและรหัสผ่านเพื่อเริ่มใช้งาน</p>

            @if (session('status'))
                <div class="mt-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 ring-1 ring-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf

                {{-- อีเมล --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">อีเมล</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        </span>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username" placeholder="name@company.com"
                               class="block w-full rounded-lg border-0 py-2.5 pl-11 pr-3 text-slate-900 ring-1 ring-inset {{ $errors->has('email') ? 'ring-red-400' : 'ring-slate-300' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- รหัสผ่าน + ปุ่มดู/ซ่อน --}}
                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-700">รหัสผ่าน</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                ลืมรหัสผ่าน?
                            </a>
                        @endif
                    </div>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        </span>
                        <input id="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                               :type="show ? 'text' : 'password'"
                               type="password"
                               class="block w-full rounded-lg border-0 py-2.5 pl-11 pr-12 text-slate-900 ring-1 ring-inset {{ $errors->has('password') ? 'ring-red-400' : 'ring-slate-300' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">

                        <button type="button" @click="show = !show"
                                :aria-label="show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
                                :aria-pressed="show"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3.5 text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:text-indigo-600">
                            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- จดจำฉัน --}}
                <label for="remember_me" class="flex items-center gap-2.5 text-sm text-slate-600">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                    จดจำฉันไว้ในเครื่องนี้
                </label>

                <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2 active:bg-indigo-700">
                    เข้าสู่ระบบ
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} {{ $companyName }}
        </p>
    </div>
</div>
</body>
</html>