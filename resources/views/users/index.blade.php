@extends('layouts.app')

@section('title', 'จัดการพนักงาน')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">รายชื่อพนักงาน</h1>
                <p class="text-sm text-slate-500">
                    @if ($search !== '')
                        พบ {{ number_format($users->total()) }} คน จากคำค้น "{{ $search }}"
                    @else
                        ทั้งหมด {{ number_format($users->total()) }} คน
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- ค้นหา: ชื่อ / อีเมล / ตำแหน่ง --}}
                <form action="{{ route('users.index') }}" method="GET" role="search" class="relative">
                    <label for="q" class="sr-only">ค้นหาพนักงาน</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="q" type="search" name="q" value="{{ $search }}" placeholder="ค้นหาชื่อ อีเมล หรือตำแหน่ง"
                           class="w-64 rounded-lg border border-slate-300 py-2 pl-9 pr-9 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    @if ($search !== '')
                        <a href="{{ route('users.index') }}" aria-label="ล้างการค้นหา"
                           class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </form>

                <button type="button" data-drawer-open="create"
                        class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    เพิ่มพนักงาน
                </button>
            </div>
        </div>

        <!-- ข้อความแจ้งเตือน -->
        @if (session('success'))
            <div class="mx-5 mt-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-5 mt-4 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">พนักงาน</th>
                        <th class="px-5 py-3 font-medium">อีเมล</th>
                        <th class="px-5 py-3 font-medium">ตำแหน่ง</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors">

                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                        @if ($user->id === auth()->id())
                                            <span class="mt-0.5 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">บัญชีของคุณ</span>
                                        @endif
                                        @unless ($user->is_active)
                                            <span class="mt-0.5 inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">ปิดใช้งาน</span>
                                        @endunless
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-500">{{ $user->email }}</td>

                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-700">{{ ucfirst($role->name) }}</span>
                                    @empty
                                        <span class="text-slate-400">-</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    {{-- ปุ่มแก้ไข: เปิด drawer พร้อมส่งข้อมูลผ่าน data-* --}}
                                    <button type="button"
                                            data-drawer-open="edit"
                                            data-id="{{ $user->id }}"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-role="{{ $user->roles->first()?->name }}"
                                            title="แก้ไข" aria-label="แก้ไข {{ $user->name }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        แก้ไข
                                    </button>

                                    @if ($user->google2fa_enabled || ! empty($user->getRawOriginal('google2fa_secret')))
                                        <form action="{{ route('users.reset-2fa', $user->id) }}" method="POST"
                                              onsubmit="return confirm('รีเซ็ต 2FA ของ {{ e($user->name) }}? ผู้ใช้ต้องตั้งค่า 2FA ใหม่เอง');">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50 transition-colors">
                                                รีเซ็ต 2FA
                                            </button>
                                        </form>
                                    @endif

                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('users.toggle-active', $user->id) }}" method="POST"
                                              onsubmit="return confirm('{{ $user->is_active ? 'ปิดใช้งานบัญชีนี้? ผู้ใช้จะเข้าสู่ระบบไม่ได้' : 'เปิดใช้งานบัญชีนี้อีกครั้ง?' }}');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                                {{ $user->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                              onsubmit="return confirm('ยืนยันการลบพนักงานคนนี้?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    title="ลบ" aria-label="ลบ {{ $user->name }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600 hover:border-red-600 hover:text-white transition-colors">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                ลบ
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                </div>
                                @if ($search !== '')
                                    <p class="font-medium text-slate-900">ไม่พบพนักงานที่ตรงกับ "{{ $search }}"</p>
                                    <p class="mb-4 text-sm text-slate-500">ลองใช้ชื่อ อีเมล หรือตำแหน่งอื่น</p>
                                    <a href="{{ route('users.index') }}"
                                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        ล้างการค้นหา
                                    </a>
                                @else
                                    <p class="font-medium text-slate-900">ยังไม่มีพนักงานในระบบ</p>
                                    <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มพนักงานคนแรก</p>
                                    <button type="button" data-drawer-open="create"
                                            class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                        เพิ่มพนักงาน
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </section>

    {{-- ===================== Side drawer: เพิ่ม/แก้ไขพนักงาน ===================== --}}
    <div id="userDrawer" class="invisible fixed inset-0 z-50" aria-hidden="true">

        {{-- พื้นหลังมืด --}}
        <div data-drawer-close
             class="absolute inset-0 bg-slate-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-200 motion-reduce:transition-none"></div>

        {{-- แผง --}}
        <aside role="dialog" aria-modal="true" aria-labelledby="drawerTitle"
               class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-200 ease-out motion-reduce:transition-none">

            <form id="userForm" method="POST" action="{{ route('users.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_method" value="PUT" disabled>
                <input type="hidden" name="_mode" value="{{ old('_mode', 'create') }}">
                <input type="hidden" name="_user_id" value="{{ old('_user_id') }}">

                {{-- หัว --}}
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-5">
                    <div>
                        <h2 id="drawerTitle" class="text-lg font-semibold text-slate-900">เพิ่มพนักงานใหม่</h2>
                        <p id="drawerSubtitle" class="text-sm text-slate-500">สร้างบัญชีสำหรับเข้าสู่ระบบ</p>
                    </div>
                    <button type="button" data-drawer-close aria-label="ปิด"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- เนื้อหา (เลื่อนได้) --}}
                <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">

                    @if ($errors->any())
                        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                            <p class="mb-1 font-medium">กรุณาตรวจสอบข้อมูลอีกครั้ง</p>
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label for="f_name" class="mb-1.5 block text-sm font-medium text-slate-700">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input id="f_name" type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>

                    <div>
                        <label for="f_email" class="mb-1.5 block text-sm font-medium text-slate-700">อีเมล <span class="text-red-500">*</span></label>
                        <input id="f_email" type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>

                    <div>
                        <label for="f_role" class="mb-1.5 block text-sm font-medium text-slate-700">ตำแหน่ง <span class="text-red-500">*</span></label>
                        <select id="f_role" name="role" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                            <option value="">-- เลือกตำแหน่ง --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f_password" class="mb-1.5 block text-sm font-medium text-slate-700">
                            <span id="pwLabel">รหัสผ่าน</span>
                            <span id="pwRequired" class="text-red-500">*</span>
                            <span id="pwHint" class="hidden font-normal text-slate-400">(เว้นว่างถ้าไม่เปลี่ยน)</span>
                        </label>
                        <input id="f_password" type="password" name="password" autocomplete="new-password" placeholder="อย่างน้อย 8 ตัวอักษร"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>

                    <div>
                        <label for="f_password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">
                            ยืนยันรหัสผ่าน <span id="pwcRequired" class="text-red-500">*</span>
                        </label>
                        <input id="f_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                {{-- ท้าย --}}
                <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
                    <button type="button" data-drawer-close
                            class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">ยกเลิก</button>
                    <button type="submit" id="drawerSubmit"
                            class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">บันทึก</button>
                </div>
            </form>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const drawer  = document.getElementById('userDrawer');
    const panel   = drawer.querySelector('aside');
    const overlay = drawer.querySelector('[data-drawer-close]');
    const form    = document.getElementById('userForm');

    const f = {
        mode:   form.querySelector('[name=_mode]'),
        userId: form.querySelector('[name=_user_id]'),
        method: form.querySelector('[name=_method]'),
        name:   form.querySelector('[name=name]'),
        email:  form.querySelector('[name=email]'),
        role:   form.querySelector('[name=role]'),
        pw:     form.querySelector('[name=password]'),
        pwc:    form.querySelector('[name=password_confirmation]'),
    };
    const el = {
        title:    document.getElementById('drawerTitle'),
        subtitle: document.getElementById('drawerSubtitle'),
        pwLabel:  document.getElementById('pwLabel'),
        pwReq:    document.getElementById('pwRequired'),
        pwHint:   document.getElementById('pwHint'),
        pwcReq:   document.getElementById('pwcRequired'),
    };

    const storeUrl  = @json(route('users.store'));
    const updateUrl = @json(route('users.update', ':id'));
    let lastFocus = null;

    function setMode(mode, data = {}) {
        const isEdit = mode === 'edit';

        f.mode.value   = mode;
        f.userId.value = isEdit ? data.id : '';
        f.method.disabled = !isEdit;                       // เปิด _method=PUT เฉพาะตอนแก้ไข
        form.action = isEdit ? updateUrl.replace(':id', data.id) : storeUrl;

        el.title.textContent    = isEdit ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่';
        el.subtitle.textContent = isEdit ? 'แก้ไขข้อมูลและตำแหน่งของพนักงาน' : 'สร้างบัญชีสำหรับเข้าสู่ระบบ';
        el.pwLabel.textContent  = isEdit ? 'รหัสผ่านใหม่' : 'รหัสผ่าน';

        // แก้ไข: รหัสผ่านไม่บังคับ / เพิ่มใหม่: บังคับ
        f.pw.required  = !isEdit;
        f.pwc.required = !isEdit;
        el.pwReq.classList.toggle('hidden', isEdit);
        el.pwcReq.classList.toggle('hidden', isEdit);
        el.pwHint.classList.toggle('hidden', !isEdit);
    }

    function open(mode, data = {}, keepValues = false) {
        setMode(mode, data);

        if (!keepValues) {                                 // keepValues = เปิดซ้ำหลัง validate ไม่ผ่าน (ใช้ old())
            f.name.value  = data.name  ?? '';
            f.email.value = data.email ?? '';
            f.role.value  = data.role  ?? '';
            f.pw.value = f.pwc.value = '';
            drawer.querySelectorAll('.bg-red-50').forEach(e => e.remove());
        }

        lastFocus = document.activeElement;
        drawer.classList.remove('invisible');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {                      // ให้ transition ทำงาน
            overlay.classList.remove('opacity-0');
            panel.classList.remove('translate-x-full');
        });
        setTimeout(() => f.name.focus(), 200);
    }

    function close() {
        overlay.classList.add('opacity-0');
        panel.classList.add('translate-x-full');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => drawer.classList.add('invisible'), 200);
        lastFocus?.focus?.();
    }

    document.addEventListener('click', (e) => {
        const openBtn = e.target.closest('[data-drawer-open]');
        if (openBtn) {
            const d = openBtn.dataset;
            open(d.drawerOpen, { id: d.id, name: d.name, email: d.email, role: d.role });
            return;
        }
        if (e.target.closest('[data-drawer-close]')) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') close();
    });

    // กันกดบันทึกซ้ำ
    form.addEventListener('submit', () => {
        const btn = document.getElementById('drawerSubmit');
        btn.disabled = true;
        btn.classList.add('opacity-60');
    });

    // validate ไม่ผ่าน → เปิด drawer กลับมาพร้อมค่าเดิมและ error
    @if ($errors->any() && old('_mode'))
        open(@json(old('_mode')), { id: @json(old('_user_id')) }, true);
        f.role.value = @json(old('role', ''));
    @endif
})();
</script>
@endpush
