<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Mini ERP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"IBM Plex Sans Thai"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        ink: {
                            700: '#24385a',
                            800: '#1a2a47',
                            900: '#122038',
                            950: '#0c1729',
                        },
                        amber: { 400: '#f5b04c', 500: '#f2a02b' },
                    },
                },
            },
        }
    </script>

    <style>
        body { font-feature-settings: "tnum"; }

        /* scrollbar ของเมนูด้านข้างให้บางลง */
        .nav-scroll::-webkit-scrollbar { width: 6px; }
        .nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 9999px; }

        /* ซ่อน sidebar บนจอใหญ่ */
        @media (min-width: 1024px) {
            body.sidebar-collapsed #sidebar { display: none; }
        }

        /* สไตล์สำหรับการพิมพ์ */
        @media print {
            @page { margin: 1cm; size: A4; }
            body { background-color: white !important; }
        }
    </style>
</head>

@php
    // กำหนดเมนูไว้ที่เดียว: [label, route, pattern ของ routeIs, icon path]
    $navGroups = [
        'ภาพรวม' => [
            ['แดชบอร์ด', 'dashboard', 'dashboard',
                'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
        ],
        'คลังสินค้า' => [
            ['สินค้า', 'products.index', 'products.*',
                'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
            ['ใบรับ/ใบเบิกสินค้า', 'stock-documents.index', 'stock-documents.*',
                'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ],
        'ข้อมูลหลัก' => [
            ['หมวดหมู่', 'categories.index', 'categories.*',
                'M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122'],
            ['ยี่ห้ออะไหล่', 'brands.index', 'brands.*',
                'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z'],
            ['ยี่ห้อ/รุ่นรถ', 'car-brands.index', 'car-brands.*',
                'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'],
            ['หน่วยนับ', 'units.index', 'units.*',
                'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z'],
        ],
        'คู่ค้า' => [
            ['ลูกค้า', 'customers.index', 'customers.*',
                'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['ซัพพลายเออร์', 'suppliers.index', 'suppliers.*',
                'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z'],
        ],
    ];

    $userName = Auth::user()->name;
    $initial  = mb_substr($userName, 0, 1);
@endphp

<body class="bg-slate-100 font-sans text-slate-800 antialiased flex h-screen overflow-hidden print:block print:h-auto print:overflow-visible print:bg-white">

    <script>
        try { if (localStorage.getItem('sidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed'); } catch (e) {}
    </script>

    <!-- Overlay สำหรับมือถือ (ซ่อนเวลาพิมพ์) -->
    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 z-30 bg-ink-950/60 backdrop-blur-sm hidden lg:hidden print:hidden"></div>

    <!-- Sidebar (ซ่อนเวลาพิมพ์) -->
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transition-transform duration-200
                  lg:static lg:translate-x-0
                  bg-ink-900 text-slate-300 flex-shrink-0 flex flex-col print:hidden">

        <!-- โลโก้ -->
        <div class="h-16 px-5 flex items-center gap-3 border-b border-white/10">
            <div class="h-9 w-9 rounded-lg bg-amber-500 text-ink-950 flex items-center justify-center font-bold text-lg">M</div>
            <span class="text-lg font-semibold text-white tracking-tight">Mini ERP</span>
            <button type="button" onclick="toggleSidebar()" class="ml-auto lg:hidden text-slate-400 hover:text-white" aria-label="ปิดเมนู">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- เมนู -->
        <nav class="nav-scroll flex-1 overflow-y-auto px-3 py-4 space-y-6">
            @foreach ($navGroups as $groupName => $items)
                <div>
                    <div class="px-3 mb-2 text-xs font-medium text-slate-500">{{ $groupName }}</div>
                    <div class="space-y-0.5">
                        @foreach ($items as [$label, $routeName, $pattern, $icon])
                            @php $active = request()->routeIs($pattern); @endphp
                            <a href="{{ route($routeName) }}"
                               @if($active) aria-current="page" @endif
                               class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors
                                      {{ $active ? 'bg-white/10 text-white font-medium' : 'hover:bg-white/5 hover:text-white' }}
                                      focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                                @if($active)
                                    <span class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r bg-amber-500"></span>
                                @endif
                                <svg class="h-5 w-5 flex-shrink-0 {{ $active ? 'text-amber-400' : 'text-slate-400 group-hover:text-slate-200' }}"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                                </svg>
                                <span class="truncate">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <!-- ผู้ใช้ + ออกจากระบบ -->
        <div class="p-4 border-t border-white/10">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-9 w-9 rounded-full bg-ink-700 text-white flex items-center justify-center text-sm font-semibold">{{ $initial }}</div>
                <div class="min-w-0">
                    <div class="text-xs text-slate-500">ล็อกอินในชื่อ</div>
                    <div class="text-sm text-white truncate">{{ $userName }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-lg border border-white/15 py-2 text-sm text-slate-300
                               hover:bg-red-500 hover:border-red-500 hover:text-white transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    ออกจากระบบ
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 flex flex-col overflow-hidden print:block print:overflow-visible">

        <!-- Header (ซ่อนเวลาพิมพ์) -->
        <header class="h-16 flex-shrink-0 bg-white border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 print:hidden">
            <button type="button" onclick="toggleSidebar()" class="-ml-1 p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500" aria-label="เปิด/ซ่อนเมนู" title="เปิด/ซ่อนเมนู">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <h2 class="text-xl font-semibold text-slate-900 truncate">@yield('title', 'Dashboard')</h2>

            <div class="ml-auto flex items-center gap-3">
                @yield('header_actions')
                @php
                    // ใช้ route ที่มีอยู่จริง ถ้ายังไม่มีจะเป็น '#'
                    $profileUrl = Route::has('profile.show') ? route('profile.show')
                                : (Route::has('profile.edit') ? route('profile.edit') : '#');
                    $profileEditUrl = Route::has('profile.edit') ? route('profile.edit') : $profileUrl;
                @endphp

                <!-- เมนูผู้ใช้: แสดงเมื่อเอาเมาส์ชี้ (หรือโฟกัสด้วยคีย์บอร์ด/แตะบนจอสัมผัส) -->
                <div class="group relative z-50 hidden sm:block pl-3 border-l border-slate-200">
                    <button type="button" aria-haspopup="true"
                            class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100 group-focus-within:bg-slate-100 focus:outline-none transition-colors">
                        <span class="h-8 w-8 rounded-full bg-ink-800 text-white flex items-center justify-center text-sm font-semibold">{{ $initial }}</span>
                        <span class="text-sm text-slate-700 max-w-[10rem] truncate">{{ $userName }}</span>
                        <svg class="h-4 w-4 text-slate-400 transition-transform group-hover:rotate-180 group-focus-within:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <!-- pt-2 ทำให้เมาส์เลื่อนจากปุ่มลงมาที่เมนูได้โดยเมนูไม่หาย -->
                    <div class="invisible absolute right-0 top-full w-64 pt-2 opacity-0 transition duration-150
                                group-hover:visible group-hover:opacity-100
                                group-focus-within:visible group-focus-within:opacity-100">
                        <div class="overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">

                            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                                <span class="h-10 w-10 flex-shrink-0 rounded-full bg-ink-800 text-white flex items-center justify-center font-semibold">{{ $initial }}</span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-slate-900">{{ $userName }}</div>
                                    <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                                </div>
                            </div>

                            <div class="p-1.5">
                                <a href="{{ $profileUrl }}"
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    ข้อมูลโปรไฟล์
                                </a>
                                <a href="{{ $profileEditUrl }}"
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    แก้ไขข้อมูลส่วนตัว
                                </a>
                            </div>

                            <div class="border-t border-slate-100 p-1.5">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                        ออกจากระบบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area (แสดงเต็มหน้าเวลาพิมพ์) -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 print:p-0 print:bg-white print:overflow-visible">

            <!-- แจ้งเตือนจากระบบ -->
            @if (session('success'))
                <div class="mb-4 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden" role="status">
                    <svg class="h-5 w-5 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 print:hidden" role="alert">
                    <svg class="h-5 w-5 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 print:hidden" role="alert">
                    <div class="font-medium mb-1">กรุณาตรวจสอบข้อมูลอีกครั้ง</div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function toggleSidebar() {
            // จอใหญ่: ซ่อน/แสดง sidebar แล้วจำค่าไว้
            if (window.matchMedia('(min-width: 1024px)').matches) {
                const collapsed = document.body.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
                return;
            }
            // มือถือ/แท็บเล็ต: เลื่อนเมนูเข้าออกพร้อม overlay
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.toggle('hidden');
        }
    </script>
    @stack('scripts')
</body>
</html>
