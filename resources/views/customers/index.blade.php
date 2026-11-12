@extends('layouts.app')

@section('title', 'จัดการลูกค้า')

@section('content')
    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
            <svg class="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
            <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">รายชื่อลูกค้าทั้งหมด</h1>
                    <p class="text-sm text-slate-500">
                        @if ($search !== '' || $status !== '')
                            พบ {{ number_format($customers->total()) }} รายการ
                            @if ($search !== '')
                                จากคำค้น "<span class="font-medium text-slate-700">{{ $search }}</span>"
                            @endif
                        @else
                            ทั้งหมด {{ number_format($customers->total()) }} รายการ
                        @endif
                    </p>
                </div>
                <button type="button" data-open-create
                        class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    เพิ่มลูกค้าใหม่
                </button>
            </div>

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('customers.index') }}" class="mt-4 flex flex-wrap items-center gap-2">
                <div class="relative min-w-[16rem] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="ค้นหาชื่อ, เบอร์โทร, ทะเบียนรถ, อีเมล, ที่อยู่..."
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                </div>

                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="" @selected($status === '')>ทุกสถานะ</option>
                    <option value="active" @selected($status === 'active')>เปิดใช้งาน</option>
                    <option value="inactive" @selected($status === 'inactive')>ปิดใช้งาน</option>
                </select>

                <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 transition-colors">
                    ค้นหา
                </button>

                @if ($search !== '' || $status !== '')
                    <a href="{{ route('customers.index') }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                        ล้าง
                    </a>
                @endif
            </form>
        </div>

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">ชื่อลูกค้า / อู่</th>
                        <th class="px-5 py-3 font-medium">เบอร์โทร</th>
                        <th class="px-5 py-3 font-medium">ทะเบียนรถ</th>
                        <th class="px-5 py-3 font-medium">ที่อยู่</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        @php
                            $customerData = [
                                'id'            => $customer->id,
                                'name'          => $customer->name,
                                'phone'         => $customer->phone,
                                'email'         => $customer->email,
                                'license_plate' => $customer->license_plate,
                                'address'       => $customer->address,
                                'is_active'     => (bool) $customer->is_active,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                                        {{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <span class="font-medium text-slate-900">{{ $customer->name }}</span>
                                        @unless ($customer->is_active)
                                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">ปิดใช้งาน</span>
                                        @endunless
                                        @if ($customer->email)
                                            <div class="text-xs text-slate-400">{{ $customer->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-600">{{ $customer->phone ?? '-' }}</td>

                            <td class="px-5 py-3">
                                @if ($customer->license_plate)
                                    <span class="inline-flex rounded-md bg-amber-50 px-2.5 py-0.5 font-mono text-xs font-semibold text-amber-800 ring-1 ring-amber-200">{{ $customer->license_plate }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <td class="max-w-xs truncate px-5 py-3 text-slate-500" title="{{ $customer->address }}">
                                {{ $customer->address ? Str::limit($customer->address, 30) : '-' }}
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            data-open-edit
                                            data-customer="{{ json_encode($customerData, JSON_UNESCAPED_UNICODE) }}"
                                            title="แก้ไข" aria-label="แก้ไข {{ $customer->name }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        แก้ไข
                                    </button>

                                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการลบลูกค้ารายนี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="ลบ" aria-label="ลบ {{ $customer->name }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600 hover:border-red-600 hover:text-white transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                </div>
                                @if ($search !== '' || $status !== '')
                                    <p class="font-medium text-slate-900">ไม่พบลูกค้าที่ตรงกับการค้นหา</p>
                                    <p class="mb-4 text-sm text-slate-500">ลองเปลี่ยนคำค้นหรือล้างตัวกรอง</p>
                                    <a href="{{ route('customers.index') }}"
                                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        ล้างการค้นหา
                                    </a>
                                @else
                                    <p class="font-medium text-slate-900">ยังไม่มีลูกค้าในระบบ</p>
                                    <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มลูกค้ารายแรกของคุณ</p>
                                    <button type="button" data-open-create
                                            class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                        เพิ่มลูกค้าใหม่
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if ($customers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $customers->withQueryString()->links() }}
            </div>
        @endif
    </section>

    {{-- ===================== Drawer ฟอร์ม เพิ่ม/แก้ไข ===================== --}}
    <div id="customerDrawer"
         class="fixed inset-0 z-50 hidden"
         role="dialog" aria-modal="true" aria-labelledby="drawerTitle"
         data-store-url="{{ route('customers.store') }}"
         data-update-url="{{ route('customers.update', '__ID__') }}"
         data-reopen="{{ $errors->any() ? old('_form') : '' }}"
         data-reopen-id="{{ $errors->any() ? old('_id') : '' }}">

        {{-- Backdrop --}}
        <div id="drawerBackdrop" class="absolute inset-0 bg-slate-900/40 opacity-0 transition-opacity duration-300"></div>

        {{-- Panel --}}
        <div id="drawerPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white shadow-xl transition-transform duration-300">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 id="drawerTitle" class="text-lg font-semibold text-slate-900">เพิ่มลูกค้าใหม่</h2>
                <button type="button" data-close-drawer aria-label="ปิด"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="customerForm" method="POST" action="{{ route('customers.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="formMode" value="create">
                <input type="hidden" name="_id" id="formId" value="">

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                    @if ($errors->any())
                        <div id="formErrors" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label for="f_name" class="mb-1.5 block text-sm font-medium text-slate-700">ชื่อลูกค้า/อู่ซ่อม <span class="text-red-500">*</span></label>
                        <input type="text" id="f_name" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    </div>
                    <div>
                        <label for="f_phone" class="mb-1.5 block text-sm font-medium text-slate-700">เบอร์โทรศัพท์</label>
                        <input type="text" id="f_phone" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    </div>
                    <div>
                        <label for="f_email" class="mb-1.5 block text-sm font-medium text-slate-700">อีเมล</label>
                        <input type="email" id="f_email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    </div>
                    <div>
                        <label for="f_license_plate" class="mb-1.5 block text-sm font-medium text-slate-700">ทะเบียนรถ</label>
                        <input type="text" id="f_license_plate" name="license_plate" value="{{ old('license_plate') }}" placeholder="เช่น 1กข-1234"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    </div>
                    <div>
                        <label for="f_address" class="mb-1.5 block text-sm font-medium text-slate-700">ที่อยู่</label>
                        <textarea id="f_address" name="address" rows="3"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">{{ old('address') }}</textarea>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="f_is_active" name="is_active" value="1"
                               {{ old('_form') ? (old('is_active') ? 'checked' : '') : 'checked' }}
                               class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        <label for="f_is_active" class="ml-2 text-sm text-slate-700">เปิดใช้งาน</label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" data-close-drawer
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" id="formSubmit"
                            class="rounded-lg bg-ink-900 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 transition-colors">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const drawer    = document.getElementById('customerDrawer');
    const panel     = document.getElementById('drawerPanel');
    const backdrop  = document.getElementById('drawerBackdrop');
    const form      = document.getElementById('customerForm');
    const title     = document.getElementById('drawerTitle');
    const submitBtn = document.getElementById('formSubmit');
    const method    = document.getElementById('formMethod');
    const mode      = document.getElementById('formMode');
    const idInput   = document.getElementById('formId');

    const storeUrl  = drawer.dataset.storeUrl;
    const updateUrl = drawer.dataset.updateUrl;

    const f = {
        name:          document.getElementById('f_name'),
        phone:         document.getElementById('f_phone'),
        email:         document.getElementById('f_email'),
        license_plate: document.getElementById('f_license_plate'),
        address:       document.getElementById('f_address'),
        is_active:     document.getElementById('f_is_active'),
    };

    function show() {
        drawer.classList.remove('hidden');
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('translate-x-full');
        });
        document.body.classList.add('overflow-hidden');
        setTimeout(() => f.name.focus(), 300);
    }

    function hide() {
        backdrop.classList.add('opacity-0');
        panel.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => drawer.classList.add('hidden'), 300);
    }

    function clearErrors() {
        const box = document.getElementById('formErrors');
        if (box) box.remove();
    }

    function setCreateMode() {
        form.action = storeUrl;
        method.disabled = true;
        mode.value = 'create';
        idInput.value = '';
        title.textContent = 'เพิ่มลูกค้าใหม่';
        submitBtn.textContent = 'บันทึก';
    }

    function setEditMode(id) {
        form.action = updateUrl.replace('__ID__', id);
        method.disabled = false;
        mode.value = 'edit';
        idInput.value = id;
        title.textContent = 'แก้ไขข้อมูลลูกค้า';
        submitBtn.textContent = 'อัปเดตข้อมูล';
    }

    function openCreate() {
        form.reset();
        Object.values(f).forEach(el => { if (el.type !== 'checkbox') el.value = ''; });
        setCreateMode();
        f.is_active.checked = true;
        clearErrors();
        show();
    }

    function openEdit(c) {
        setEditMode(c.id);
        f.name.value          = c.name ?? '';
        f.phone.value         = c.phone ?? '';
        f.email.value         = c.email ?? '';
        f.license_plate.value = c.license_plate ?? '';
        f.address.value       = c.address ?? '';
        f.is_active.checked   = !!c.is_active;
        clearErrors();
        show();
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-open-create]')) return openCreate();

        const editBtn = e.target.closest('[data-open-edit]');
        if (editBtn) return openEdit(JSON.parse(editBtn.dataset.customer));

        if (e.target.closest('[data-close-drawer]') || e.target === backdrop) hide();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !drawer.classList.contains('hidden')) hide();
    });

    // เปิด drawer ค้างไว้อัตโนมัติเมื่อ validate ไม่ผ่าน (ค่าเดิมถูกเติมด้วย old() แล้ว)
    const reopen = drawer.dataset.reopen;
    if (reopen === 'edit' && drawer.dataset.reopenId) {
        setEditMode(drawer.dataset.reopenId);
        show();
    } else if (reopen === 'create') {
        setCreateMode();
        show();
    }
})();
</script>
@endpush
