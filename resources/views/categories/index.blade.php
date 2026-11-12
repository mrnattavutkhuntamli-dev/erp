@extends('layouts.app')

@section('title', 'จัดการหมวดหมู่')

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
                    <h1 class="text-lg font-semibold text-slate-900">รายการหมวดหมู่ทั้งหมด</h1>
                    <p class="text-sm text-slate-500">
                        @if ($search !== '')
                            พบ {{ number_format($categories->total()) }} รายการ
                            จากคำค้น "<span class="font-medium text-slate-700">{{ $search }}</span>"
                        @else
                            ทั้งหมด {{ number_format($categories->total()) }} รายการ
                        @endif
                    </p>
                </div>
                <button type="button" data-open-create
                        class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    เพิ่มหมวดหมู่
                </button>
            </div>

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('categories.index') }}" class="mt-4 flex flex-wrap items-center gap-2">
                <div class="relative min-w-[16rem] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="ค้นหาชื่อหมวดหมู่, slug, รายละเอียด..."
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                </div>

                <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 transition-colors">
                    ค้นหา
                </button>

                @if ($search !== '')
                    <a href="{{ route('categories.index') }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                        ล้าง
                    </a>
                @endif
            </form>
        </div>

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">ชื่อหมวดหมู่</th>
                        <th class="px-5 py-3 font-medium">Slug</th>
                        <th class="px-5 py-3 font-medium">รายละเอียด</th>
                        <th class="px-5 py-3 font-medium text-center">สินค้า</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        @php
                            $categoryData = [
                                'id'          => $category->id,
                                'name'        => $category->name,
                                'description' => $category->description,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                                        {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $category->name }}</span>
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 font-mono text-xs font-medium text-slate-700">{{ $category->slug }}</span>
                            </td>

                            <td class="max-w-xs truncate px-5 py-3 text-slate-500" title="{{ $category->description }}">
                                {{ $category->description ? Str::limit($category->description, 40) : '-' }}
                            </td>

                            <td class="px-5 py-3 text-center text-slate-600">
                                {{ number_format($category->products_count) }}
                            </td>

                            {{-- ปุ่มจัดการ --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            data-open-edit
                                            data-category="{{ json_encode($categoryData, JSON_UNESCAPED_UNICODE) }}"
                                            title="แก้ไข" aria-label="แก้ไข {{ $category->name }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        แก้ไข
                                    </button>

                                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="ลบ" aria-label="ลบ {{ $category->name }}"
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
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                                </div>
                                @if ($search !== '')
                                    <p class="font-medium text-slate-900">ไม่พบหมวดหมู่ที่ตรงกับการค้นหา</p>
                                    <p class="mb-4 text-sm text-slate-500">ลองเปลี่ยนคำค้นหรือล้างตัวกรอง</p>
                                    <a href="{{ route('categories.index') }}"
                                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        ล้างการค้นหา
                                    </a>
                                @else
                                    <p class="font-medium text-slate-900">ยังไม่มีหมวดหมู่ในระบบ</p>
                                    <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มหมวดหมู่แรกของคุณ</p>
                                    <button type="button" data-open-create
                                            class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                        เพิ่มหมวดหมู่
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if ($categories->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $categories->withQueryString()->links() }}
            </div>
        @endif
    </section>

    {{-- ===================== Drawer ฟอร์ม เพิ่ม/แก้ไข ===================== --}}
    <div id="categoryDrawer"
         class="fixed inset-0 z-50 hidden"
         role="dialog" aria-modal="true" aria-labelledby="drawerTitle"
         data-store-url="{{ route('categories.store') }}"
         data-update-url="{{ route('categories.update', '__ID__') }}"
         data-reopen="{{ $errors->any() ? old('_form') : '' }}"
         data-reopen-id="{{ $errors->any() ? old('_id') : '' }}">

        {{-- Backdrop --}}
        <div id="drawerBackdrop" class="absolute inset-0 bg-slate-900/40 opacity-0 transition-opacity duration-300"></div>

        {{-- Panel --}}
        <div id="drawerPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white shadow-xl transition-transform duration-300">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 id="drawerTitle" class="text-lg font-semibold text-slate-900">เพิ่มหมวดหมู่ใหม่</h2>
                <button type="button" data-close-drawer aria-label="ปิด"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="categoryForm" method="POST" action="{{ route('categories.store') }}" class="flex min-h-0 flex-1 flex-col">
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
                        <label for="f_name" class="mb-1.5 block text-sm font-medium text-slate-700">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                        <input type="text" id="f_name" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    </div>
                    <div>
                        <label for="f_description" class="mb-1.5 block text-sm font-medium text-slate-700">รายละเอียด</label>
                        <textarea id="f_description" name="description" rows="4"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">{{ old('description') }}</textarea>
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
    const drawer    = document.getElementById('categoryDrawer');
    const panel     = document.getElementById('drawerPanel');
    const backdrop  = document.getElementById('drawerBackdrop');
    const form      = document.getElementById('categoryForm');
    const title     = document.getElementById('drawerTitle');
    const submitBtn = document.getElementById('formSubmit');
    const method    = document.getElementById('formMethod');
    const mode      = document.getElementById('formMode');
    const idInput   = document.getElementById('formId');

    const storeUrl  = drawer.dataset.storeUrl;
    const updateUrl = drawer.dataset.updateUrl;

    const f = {
        name:        document.getElementById('f_name'),
        description: document.getElementById('f_description'),
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
        title.textContent = 'เพิ่มหมวดหมู่ใหม่';
        submitBtn.textContent = 'บันทึก';
    }

    function setEditMode(id) {
        form.action = updateUrl.replace('__ID__', id);
        method.disabled = false;
        mode.value = 'edit';
        idInput.value = id;
        title.textContent = 'แก้ไขหมวดหมู่';
        submitBtn.textContent = 'อัปเดตข้อมูล';
    }

    function openCreate() {
        form.reset();
        Object.values(f).forEach(el => { el.value = ''; });
        setCreateMode();
        clearErrors();
        show();
    }

    function openEdit(c) {
        setEditMode(c.id);
        f.name.value        = c.name ?? '';
        f.description.value = c.description ?? '';
        clearErrors();
        show();
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-open-create]')) return openCreate();

        const editBtn = e.target.closest('[data-open-edit]');
        if (editBtn) return openEdit(JSON.parse(editBtn.dataset.category));

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
