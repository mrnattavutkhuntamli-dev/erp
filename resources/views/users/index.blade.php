@extends('layouts.app')

@section('title', 'จัดการพนักงาน')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">รายชื่อพนักงาน</h1>
                <p class="text-sm text-slate-500">
                    ทั้งหมด {{ number_format($users->total()) }} คน
                </p>
            </div>
            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                เพิ่มพนักงาน
            </a>
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

                            {{-- ตัวอักษรแรก + ชื่อ --}}
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

                            {{-- ปุ่มจัดการ --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('users.edit', $user->id) }}"
                                       title="แก้ไข" aria-label="แก้ไข {{ $user->name }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        แก้ไข
                                    </a>

                                    @if ($user->id !== auth()->id())
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
                                <p class="font-medium text-slate-900">ยังไม่มีพนักงานในระบบ</p>
                                <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มพนักงานคนแรก</p>
                                <a href="{{ route('users.create') }}"
                                   class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                    เพิ่มพนักงาน
                                </a>
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
@endsection
