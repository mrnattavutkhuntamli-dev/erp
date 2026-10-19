@extends('layouts.app')

@section('title', 'ยี่ห้อและรุ่นรถ')

@section('content')
    <!-- แถบหัวหน้า -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">ยี่ห้อและรุ่นรถ</h1>
            <p class="text-sm text-slate-500">ทั้งหมด {{ number_format($carBrands->count()) }} ยี่ห้อ</p>
        </div>
        <a href="{{ route('car-brands.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            เพิ่มยี่ห้อรถ
        </a>
    </div>

    <!-- การ์ดยี่ห้อรถ -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @forelse ($carBrands as $carBrand)
            <article class="flex flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

                <!-- หัวการ์ด -->
                <header class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-ink-900 text-amber-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-slate-900">{{ $carBrand->name }}</h2>
                            <p class="text-xs text-slate-500">{{ number_format($carBrand->models->count()) }} รุ่น</p>
                        </div>
                    </div>

                    <div class="flex flex-shrink-0 items-center gap-1.5">
                        <a href="{{ route('car-brands.edit', $carBrand->id) }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                            แก้ไข
                        </a>
                        <form action="{{ route('car-brands.destroy', $carBrand->id) }}" method="POST"
                              onsubmit="return confirm('ลบยี่ห้อนี้และรุ่นรถทั้งหมด?');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:border-red-600 hover:bg-red-600 hover:text-white transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                ลบ
                            </button>
                        </form>
                    </div>
                </header>

                <!-- รายการรุ่นรถ -->
                <div class="flex-1 px-5 py-4">
                    <h3 class="mb-2 text-sm font-medium text-slate-500">รุ่นรถที่มีในระบบ</h3>

                    @forelse ($carBrand->models as $model)
                        @if ($loop->first)<ul class="divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200">@endif

                        <li class="flex items-center justify-between gap-3 px-3.5 py-2.5 hover:bg-slate-50">
                            <div class="min-w-0">
                                <span class="font-medium text-slate-800">{{ $model->name }}</span>
                                @if ($model->year_start || $model->year_end)
                                    <span class="ml-1.5 text-sm text-slate-500">
                                        {{ $model->year_start ?? '?' }} – {{ $model->year_end ?? 'ปัจจุบัน' }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-shrink-0 items-center gap-3 text-sm">
                                <button type="button"
                                        class="js-edit-model font-medium text-sky-700 hover:underline"
                                        data-id="{{ $model->id }}"
                                        data-name="{{ $model->name }}"
                                        data-start="{{ $model->year_start }}"
                                        data-end="{{ $model->year_end }}">
                                    แก้ไข
                                </button>
                                <form action="{{ route('car-models.destroy', $model->id) }}" method="POST"
                                      onsubmit="return confirm('ลบรุ่นรถนี้?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 hover:underline">ลบ</button>
                                </form>
                            </div>
                        </li>

                        @if ($loop->last)</ul>@endif
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                            ยังไม่มีรุ่นรถ เพิ่มรุ่นแรกได้จากฟอร์มด้านล่าง
                        </div>
                    @endforelse
                </div>

                <!-- ฟอร์มเพิ่มรุ่นรถ -->
                <form action="{{ route('car-models.store') }}" method="POST" class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                    @csrf
                    <input type="hidden" name="car_brand_id" value="{{ $carBrand->id }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" name="name" required placeholder="ชื่อรุ่นรถ (เช่น Vigo)" aria-label="ชื่อรุ่นรถ"
                               class="min-w-[10rem] flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:border-ink-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                        <input type="number" name="year_start" placeholder="ปีเริ่ม" aria-label="ปีเริ่มผลิต"
                               class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:border-ink-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                        <input type="number" name="year_end" placeholder="ปีสิ้นสุด" aria-label="ปีสิ้นสุดการผลิต"
                               class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:border-ink-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            เพิ่มรุ่น
                        </button>
                    </div>
                </form>
            </article>
        @empty
            <div class="rounded-xl bg-white px-5 py-14 text-center ring-1 ring-slate-200 xl:col-span-2">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                </div>
                <p class="font-medium text-slate-900">ยังไม่มีข้อมูลยี่ห้อรถ</p>
                <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มยี่ห้อรถ แล้วเพิ่มรุ่นรถภายใต้ยี่ห้อนั้น</p>
                <a href="{{ route('car-brands.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                    เพิ่มยี่ห้อรถ
                </a>
            </div>
        @endforelse
    </div>

    <!-- หน้าต่างแก้ไขรุ่นรถ (แทน prompt เดิม) -->
    <dialog id="edit-model-dialog"
            class="m-auto w-full max-w-md rounded-xl p-0 shadow-xl backdrop:bg-ink-950/50">
        <form id="edit-model-form" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <h3 class="text-lg font-semibold text-slate-900">แก้ไขรุ่นรถ</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label for="edit-model-name" class="mb-1 block text-sm font-medium text-slate-700">ชื่อรุ่นรถ</label>
                    <input id="edit-model-name" type="text" name="name" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-ink-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="edit-model-start" class="mb-1 block text-sm font-medium text-slate-700">ปีเริ่มผลิต</label>
                        <input id="edit-model-start" type="number" name="year_start"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-ink-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                    </div>
                    <div>
                        <label for="edit-model-end" class="mb-1 block text-sm font-medium text-slate-700">ปีสิ้นสุดการผลิต</label>
                        <input id="edit-model-end" type="number" name="year_end"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-ink-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" id="edit-model-cancel"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script>
        (function () {
            const dialog = document.getElementById('edit-model-dialog');
            const form   = document.getElementById('edit-model-form');
            const baseUrl = @json(url('car-models'));   // ต้องตรงกับ route PUT /car-models/{id}

            // เปิดหน้าต่างแก้ไขพร้อมเติมค่าเดิม
            document.querySelectorAll('.js-edit-model').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = baseUrl + '/' + btn.dataset.id;
                    document.getElementById('edit-model-name').value  = btn.dataset.name;
                    document.getElementById('edit-model-start').value = btn.dataset.start;
                    document.getElementById('edit-model-end').value   = btn.dataset.end;
                    dialog.showModal();
                    document.getElementById('edit-model-name').focus();
                });
            });

            // ปิดหน้าต่าง: ปุ่มยกเลิก หรือคลิกนอกกรอบ (ปุ่ม Esc ปิดได้อยู่แล้ว)
            document.getElementById('edit-model-cancel').addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
        })();
    </script>
@endpush
