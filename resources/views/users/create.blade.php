@extends('layouts.app')

@section('title', 'เพิ่มพนักงานใหม่')

@section('content')
    <section class="mx-auto max-w-2xl overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- หัวการ์ด -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h1 class="text-lg font-semibold text-slate-900">เพิ่มพนักงานใหม่</h1>
            <a href="{{ route('users.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                กลับ
            </a>
        </div>

        <div class="px-5 py-5">
            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                    <p class="mb-1 font-medium">กรุณาตรวจสอบข้อมูลอีกครั้ง</p>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">อีเมล <span class="text-red-500">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>

                    <div>
                        <label for="role" class="mb-1.5 block text-sm font-medium text-slate-700">ตำแหน่ง <span class="text-red-500">*</span></label>
                        <select id="role" name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                            <option value="">-- เลือกตำแหน่ง --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">รหัสผ่าน <span class="text-red-500">*</span></label>
                            <input id="password" type="password" name="password" required placeholder="อย่างน้อย 8 ตัวอักษร" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">ยืนยันรหัสผ่าน <span class="text-red-500">*</span></label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 border-t border-slate-200 pt-5">
                    <a href="{{ route('users.index') }}"
                       class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">ยกเลิก</a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">เพิ่มพนักงานใหม่0</button>
                </div>
            </form>
        </div>
    </section>
@endsection
