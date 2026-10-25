@extends('layouts.app')
@section('title', 'ตั้งค่าข้อมูลบริษัท')

@section('content')
    @php
        $secretHints = ['token', 'key', 'secret', 'password', 'pass'];
        $logoUrl = $companySettings['company_logo'] ? asset('storage/logo/' . $companySettings['company_logo']) : null;
    @endphp

    <div class="max-w-4xl mx-auto space-y-8 pb-12">

        {{-- ========== Flash messages ========== --}}
        @if (session('success'))
            <div data-toast role="status" class="flex items-start gap-3 rounded-xl bg-emerald-50 text-emerald-800 px-4 py-3 ring-1 ring-emerald-200 transition-opacity duration-500">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm font-medium">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="flex items-start gap-3 rounded-xl bg-red-50 text-red-800 px-4 py-3 ring-1 ring-red-200">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <p class="text-sm font-medium">{{ session('error') }}</p>
            </div>
        @endif

        {{-- ========== ส่วนที่ 1: ข้อมูลบริษัท ========== --}}
        <section aria-labelledby="company-heading">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-5">
                <div>
                    <h1 id="company-heading" class="text-2xl font-bold tracking-tight text-slate-900">ตั้งค่าข้อมูลบริษัท</h1>
                    <p class="text-sm text-slate-500 mt-1">ข้อมูลนี้จะแสดงบนหัวบิล ใบกำกับภาษี และใบเสนอราคา</p>
                </div>
                <button type="button" onclick="openPanel('company')"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    แก้ไขข้อมูลบริษัท
                </button>
            </div>

            <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm overflow-hidden">
                {{-- โปรไฟล์ --}}
                <div class="relative p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-5 sm:gap-7">
                    <div class="absolute inset-x-0 top-0 h-1 bg-teal-700"></div>
                    <div class="shrink-0">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="โลโก้ {{ $companySettings['company_name'] }}"
                                 class="w-24 h-24 object-contain rounded-2xl bg-white p-2 ring-1 ring-slate-200">
                        @else
                            <button type="button" onclick="openPanel('company')" title="เพิ่มโลโก้"
                                    class="w-24 h-24 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 text-slate-400 hover:border-teal-500 hover:text-teal-600 flex flex-col items-center justify-center gap-1 transition">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-xs">เพิ่มโลโก้</span>
                            </button>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 break-words">{{ $companySettings['company_name'] }}</h2>
                        @if (!empty($companySettings['company_address']))
                            <p class="mt-2 text-slate-600 leading-relaxed whitespace-pre-line">{{ $companySettings['company_address'] }}</p>
                        @else
                            <p class="mt-2 text-sm text-amber-700">ยังไม่ได้กรอกที่อยู่ — จะไม่แสดงบนเอกสาร</p>
                        @endif
                    </div>
                </div>

                {{-- รายละเอียดติดต่อ --}}
                <dl class="grid grid-cols-1 sm:grid-cols-2 border-t border-slate-200 divide-y sm:divide-y-0 sm:divide-x divide-slate-200 bg-slate-50/60">
                    <div class="p-5 sm:p-6 flex items-center gap-4">
                        <div class="w-11 h-11 shrink-0 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-500">เบอร์โทรศัพท์</dt>
                            <dd class="mt-0.5 font-semibold text-slate-900 truncate">{{ $companySettings['company_phone'] ?: '-' }}</dd>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 flex items-center gap-4">
                        <div class="w-11 h-11 shrink-0 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-500">เลขประจำตัวผู้เสียภาษี</dt>
                            <dd class="mt-0.5 font-semibold text-slate-900 tabular-nums truncate">{{ $companySettings['company_tax_id'] ?: '-' }}</dd>
                        </div>
                    </div>
                </dl>
            </div>
        </section>

        {{-- ========== ส่วนที่ 2: การตั้งค่าเพิ่มเติม ========== --}}
        <section aria-labelledby="extra-heading" class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 id="extra-heading" class="text-lg font-bold text-slate-900">การตั้งค่าเพิ่มเติม</h2>
                    <p class="text-sm text-slate-500 mt-0.5">เช่น LINE Notify Token, API Keys, ค่าภาษี</p>
                </div>
                <button type="button" onclick="openPanel('add')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-700 focus-visible:ring-offset-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                    เพิ่มการตั้งค่า
                </button>
            </div>

            @if ($otherSettings->count() > 0)
                <ul class="divide-y divide-slate-100">
                    @foreach ($otherSettings as $setting)
                        @php
                            $isSecret = \Illuminate\Support\Str::contains(strtolower($setting->key), $secretHints) && filled($setting->value);
                        @endphp
                        <li class="px-5 sm:px-6 py-4 flex items-center gap-4 hover:bg-slate-50/70 transition">
                            {{-- Toggle --}}
                            <form action="{{ route('settings.toggle', $setting->key) }}" method="POST" class="shrink-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" role="switch" aria-checked="{{ $setting->is_active ? 'true' : 'false' }}"
                                        title="{{ $setting->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 {{ $setting->is_active ? 'bg-teal-600' : 'bg-slate-300' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $setting->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </form>

                            {{-- Key & Value --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-mono text-sm font-semibold truncate {{ $setting->is_active ? 'text-slate-900' : 'text-slate-400' }}">{{ $setting->key }}</p>
                                    @unless ($setting->is_active)
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">ปิดอยู่</span>
                                    @endunless
                                </div>
                                <div class="mt-1 flex items-center gap-1.5 min-w-0">
                                    @if (blank($setting->value))
                                        <p class="text-sm italic text-slate-400">(ว่าง)</p>
                                    @elseif ($isSecret)
                                        <p class="text-sm font-mono truncate {{ $setting->is_active ? 'text-slate-500' : 'text-slate-400' }}"
                                           data-secret="{{ $setting->value }}" data-hidden="1">••••••••••••</p>
                                        <button type="button" onclick="toggleSecret(this)" title="แสดง/ซ่อนค่า" aria-label="แสดงหรือซ่อนค่า"
                                                class="shrink-0 rounded p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    @else
                                        <p class="text-sm truncate {{ $setting->is_active ? 'text-slate-500' : 'text-slate-400' }}">{{ $setting->value }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- ปุ่มจัดการ --}}
                            <div class="flex gap-1 shrink-0">
                                <button type="button"
                                        data-key="{{ $setting->key }}"
                                        data-value="{{ $setting->value }}"
                                        data-action="{{ url('/settings/' . $setting->key . '/update') }}"
                                        onclick="openEditSetting(this)"
                                        class="rounded-lg p-2 text-slate-400 hover:text-teal-700 hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 transition" title="แก้ไข" aria-label="แก้ไข {{ $setting->key }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form action="{{ route('settings.destroy', $setting->key) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ {{ $setting->key }} ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 transition" title="ลบ" aria-label="ลบ {{ $setting->key }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <p class="mt-3 font-medium text-slate-700">ยังไม่มีการตั้งค่าเพิ่มเติม</p>
                    <p class="text-sm text-slate-500 mt-1">เพิ่มค่าที่ระบบต้องใช้ เช่น LINE Notify Token หรืออัตราภาษี</p>
                    <button type="button" onclick="openPanel('add')" class="mt-4 text-sm font-semibold text-teal-700 hover:text-teal-800 underline underline-offset-4">เพิ่มการตั้งค่าแรก</button>
                </div>
            @endif
        </section>
    </div>

    {{-- ============== Backdrop (ใช้ร่วมกันทุก Drawer) ============== --}}
    <div id="drawerBackdrop" onclick="closePanel()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300"></div>

    {{-- ============== Drawer: แก้ไขข้อมูลบริษัท ============== --}}
    <aside id="panel-company" role="dialog" aria-modal="true" aria-labelledby="company-title" aria-hidden="true"
           class="drawer fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl translate-x-full invisible transition-transform duration-300 ease-in-out flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 id="company-title" class="text-lg font-bold text-slate-900">แก้ไขข้อมูลบริษัท</h3>
            <button type="button" onclick="closePanel()" aria-label="ปิด" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            @method('PUT')
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                    <span class="block text-sm font-medium text-slate-700 mb-2">โลโก้ร้าน</span>
                    <div class="flex items-center gap-4 p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50">
                        <div class="shrink-0">
                            <img id="logoPreview" src="{{ $logoUrl }}" alt="ตัวอย่างโลโก้"
                                 class="w-20 h-20 object-contain rounded-xl bg-white p-1 ring-1 ring-slate-200 {{ $logoUrl ? '' : 'hidden' }}">
                            <div id="logoPlaceholder" class="w-20 h-20 rounded-xl bg-white ring-1 ring-slate-200 flex items-center justify-center text-slate-300 {{ $logoUrl ? 'hidden' : '' }}">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <input type="file" name="company_logo" accept="image/*" onchange="previewLogo(event)"
                                   class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-teal-700 file:text-white file:font-medium file:cursor-pointer hover:file:bg-teal-800">
                            <p class="text-xs text-slate-500 mt-2">แนะนำไฟล์สี่เหลี่ยมจัตุรัส พื้นหลังโปร่งใส</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="company_name" class="block text-sm font-medium text-slate-700 mb-1.5">ชื่อร้าน/บริษัท <span class="text-red-500">*</span></label>
                    <input id="company_name" type="text" name="company_name" value="{{ $companySettings['company_name'] }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30">
                </div>
                <div>
                    <label for="company_address" class="block text-sm font-medium text-slate-700 mb-1.5">ที่อยู่</label>
                    <textarea id="company_address" name="company_address" rows="3"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30">{{ $companySettings['company_address'] }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="company_phone" class="block text-sm font-medium text-slate-700 mb-1.5">เบอร์โทรศัพท์</label>
                        <input id="company_phone" type="text" name="company_phone" inputmode="tel" value="{{ $companySettings['company_phone'] }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30">
                    </div>
                    <div>
                        <label for="company_tax_id" class="block text-sm font-medium text-slate-700 mb-1.5">เลขประจำตัวผู้เสียภาษี</label>
                        <input id="company_tax_id" type="text" name="company_tax_id" inputmode="numeric" value="{{ $companySettings['company_tax_id'] }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 tabular-nums focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                <button type="button" onclick="closePanel()" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                <button type="submit" class="rounded-lg bg-teal-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">บันทึก</button>
            </div>
        </form>
    </aside>

    {{-- ============== Drawer: เพิ่ม Setting ใหม่ ============== --}}
    <aside id="panel-add" role="dialog" aria-modal="true" aria-labelledby="add-title" aria-hidden="true"
           class="drawer fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl translate-x-full invisible transition-transform duration-300 ease-in-out flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 id="add-title" class="text-lg font-bold text-slate-900">เพิ่มการตั้งค่าใหม่</h3>
            <button type="button" onclick="closePanel()" aria-label="ปิด" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('settings.store') }}" method="POST" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                    <label for="new_key" class="block text-sm font-medium text-slate-700 mb-1.5">ชื่อคีย์ <span class="text-red-500">*</span></label>
                    <input id="new_key" type="text" name="key" required placeholder="เช่น line_notify_token" pattern="[a-z0-9_]+"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30">
                    <p class="text-xs text-slate-500 mt-1.5">ใช้ตัวพิมพ์เล็ก ตัวเลข และ _ เท่านั้น</p>
                </div>
                <div>
                    <label for="new_value" class="block text-sm font-medium text-slate-700 mb-1.5">ค่า</label>
                    <textarea id="new_value" name="value" rows="4" placeholder="ค่าที่ต้องการบันทึก"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                <button type="button" onclick="closePanel()" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                <button type="submit" class="rounded-lg bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">เพิ่มการตั้งค่า</button>
            </div>
        </form>
    </aside>

    {{-- ============== Drawer: แก้ไข Setting ============== --}}
    <aside id="panel-edit" role="dialog" aria-modal="true" aria-labelledby="edit-title" aria-hidden="true"
           class="drawer fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl translate-x-full invisible transition-transform duration-300 ease-in-out flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 id="edit-title" class="text-lg font-bold text-slate-900">แก้ไขการตั้งค่า</h3>
            <button type="button" onclick="closePanel()" aria-label="ปิด" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editSettingForm" method="POST" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            @method('PUT')
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                    <label for="editSettingKey" class="block text-sm font-medium text-slate-700 mb-1.5">ชื่อคีย์</label>
                    <input id="editSettingKey" type="text" disabled
                           class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 font-mono text-sm text-slate-500">
                    <p class="text-xs text-slate-500 mt-1.5">ชื่อคีย์แก้ไขไม่ได้ หากต้องการเปลี่ยนให้ลบแล้วเพิ่มใหม่</p>
                </div>
                <div>
                    <label for="editSettingValue" class="block text-sm font-medium text-slate-700 mb-1.5">ค่า</label>
                    <textarea id="editSettingValue" name="value" rows="4"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/30"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                <button type="button" onclick="closePanel()" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                <button type="submit" class="rounded-lg bg-teal-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">อัปเดต</button>
            </div>
        </form>
    </aside>

    {{-- JavaScript --}}
    <script>
        let activePanel = null;

        function openPanel(name) {
            if (activePanel) closePanel(true);
            const panel = document.getElementById('panel-' + name);
            const backdrop = document.getElementById('drawerBackdrop');
            activePanel = panel;
            panel.classList.remove('invisible');
            panel.setAttribute('aria-hidden', 'false');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('overflow-hidden');
            requestAnimationFrame(() => {
                panel.classList.remove('translate-x-full');
                const first = panel.querySelector('input:not([disabled]):not([type=hidden]):not([type=file]), textarea');
                if (first) setTimeout(() => first.focus(), 300);
            });
        }

        function closePanel(immediate = false) {
            if (!activePanel) return;
            const panel = activePanel;
            activePanel = null;
            panel.classList.add('translate-x-full');
            panel.setAttribute('aria-hidden', 'true');
            setTimeout(() => panel.classList.add('invisible'), immediate ? 0 : 300);
            if (!immediate) {
                document.getElementById('drawerBackdrop').classList.add('opacity-0', 'pointer-events-none');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function openEditSetting(btn) {
            document.getElementById('editSettingKey').value = btn.dataset.key;
            document.getElementById('editSettingValue').value = btn.dataset.value ?? '';
            document.getElementById('editSettingForm').action = btn.dataset.action;
            openPanel('edit');
        }

        function previewLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('logoPreview');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('logoPlaceholder').classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        // แสดง/ซ่อนค่าที่เป็นความลับ (token, key, secret, password)
        function toggleSecret(btn) {
            const el = btn.previousElementSibling;
            const hidden = el.dataset.hidden === '1';
            el.textContent = hidden ? el.dataset.secret : '••••••••••••';
            el.dataset.hidden = hidden ? '0' : '1';
        }

        // ปิดด้วย ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePanel();
        });

        // ซ่อนข้อความสำเร็จอัตโนมัติ
        document.querySelectorAll('[data-toast]').forEach((el) => {
            setTimeout(() => { el.classList.add('opacity-0'); setTimeout(() => el.remove(), 500); }, 4000);
        });
    </script>
@endsection
