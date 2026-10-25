@php
    // เมนูอยู่ที่ config/nav.php (ใช้ร่วมกับหน้า dashboard)
    $navGroups = config('nav');

    $userName = Auth::user()->name;
    $initial  = mb_substr($userName, 0, 1);

    // ข้อมูลบริษัทจาก Settings
    $companyName = \App\Models\Setting::get('company_name', 'Mini ERP');
    $companyLogo = \App\Models\Setting::get('company_logo');

    // [รหัสภาษาของ Google, ชื่อที่แสดง, รหัสประเทศของธง, ตัวย่อ]
    $languages = [
        ['th',    'ไทย',     'th', 'TH'],
        ['en',    'English', 'gb', 'EN'],
        ['zh-CN', '中文',     'cn', 'ZH'],
        ['ja',    '日本語',   'jp', 'JA'],
        ['ko',    '한국어',   'kr', 'KO'],
    ];

    // ใช้ route ที่มีอยู่จริง ถ้ายังไม่มีจะเป็น '#'
    $profileUrl = Route::has('profile.show') ? route('profile.show')
                : (Route::has('profile.edit') ? route('profile.edit') : '#');
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ $companyName }}</title>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

        /* ซ่อนเมนูที่ยังไม่ถูกเตรียมโดย Alpine (กันกะพริบตอนโหลด) */
        [x-cloak] { display: none !important; }

        /* scrollbar บางๆ */
        .nav-scroll::-webkit-scrollbar { width: 6px; }
        .nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 9999px; }
        .nav-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.22); }
        .main-scroll::-webkit-scrollbar { width: 10px; height: 10px; }
        .main-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; border: 3px solid #f1f5f9; }
        .main-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* แถบปุ่มลัดบน header: เลื่อนซ้ายขวาได้ ซ่อน scrollbar และเฟดขอบ */
        .header-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
            -webkit-mask-image: linear-gradient(to right, transparent, #000 16px, #000 calc(100% - 16px), transparent);
                    mask-image: linear-gradient(to right, transparent, #000 16px, #000 calc(100% - 16px), transparent);
        }
        .header-scroll::-webkit-scrollbar { display: none; }

        /* ซ่อน sidebar บนจอใหญ่ */
        @media (min-width: 1024px) {
            body.sidebar-collapsed #sidebar { display: none; }
        }

        /* ซ่อน UI ของ Google Translate (แถบสีเทาด้านบน, tooltip, ไฮไลต์) */
        .goog-te-banner-frame, iframe.skiptranslate, #goog-gt-tt, .goog-te-balloon-frame { display: none !important; }
        body { top: 0 !important; }
        .goog-text-highlight { background: none !important; box-shadow: none !important; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.01ms !important; }
        }

        /* สไตล์สำหรับการพิมพ์ */
        @media print {
            @page { margin: 1cm; size: A4; }
            body { background-color: white !important; }
        }
    </style>
</head>

<body class="bg-slate-100 font-sans text-slate-800 antialiased flex h-screen overflow-hidden print:block print:h-auto print:overflow-visible print:bg-white">

    <script>
        try { if (localStorage.getItem('sidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed'); } catch (e) {}
    </script>
    @include('partials.page-loader')
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:shadow-lg print:hidden">ข้ามไปที่เนื้อหา</a>

    <!-- Overlay สำหรับมือถือ (ซ่อนเวลาพิมพ์) -->
    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 z-30 bg-ink-950/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden print:hidden"></div>

    <!-- Sidebar (ซ่อนเวลาพิมพ์) -->
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transition-transform duration-200 shadow-2xl
                  lg:static lg:translate-x-0 lg:shadow-none
                  bg-ink-900 text-slate-300 flex-shrink-0 flex flex-col print:hidden">

        <!-- โลโก้ + ชื่อร้าน -->
        <div class="h-16 px-4 flex items-center gap-3 border-b border-white/10">
            @if ($companyLogo)
                <img src="{{ asset('storage/logo/' . $companyLogo) }}"
                     alt="โลโก้ {{ $companyName }}" class="h-9 w-9 object-contain rounded-lg bg-white p-1 flex-shrink-0">
            @else
                <div class="h-9 w-9 rounded-lg bg-amber-500 text-ink-950 flex items-center justify-center font-bold text-lg notranslate flex-shrink-0" translate="no">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</div>
            @endif
            <span class="min-w-0 flex-1 text-base font-semibold text-white tracking-tight truncate notranslate" translate="no">{{ $companyName }}</span>
            <button type="button" onclick="toggleSidebar()" class="lg:hidden rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white flex-shrink-0" aria-label="ปิดเมนู">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- เมนู -->
        <nav class="nav-scroll flex-1 overflow-y-auto px-3 py-5 space-y-6" aria-label="เมนูหลัก">
            @foreach ($navGroups as $groupName => $items)
                @php
                    // นับจำนวนเมนูในกลุ่มนี้ที่ผู้ใช้มีสิทธิ์เห็น
                    $visibleCount = 0;
                    foreach ($items as $item) {
                        $permission = $item[4] ?? null;
                        if (!$permission || auth()->user()->can($permission)) {
                            $visibleCount++;
                        }
                    }
                @endphp

                {{-- แสดงหัวข้อกลุ่ม ก็ต่อเมื่อมีเมนูให้เห็นอย่างน้อย 1 อัน --}}
                @if ($visibleCount > 0)
                    <div>
                        <div class="px-3 mb-2 text-xs font-medium text-slate-500">{{ $groupName }}</div>
                        <div class="space-y-0.5">
                            @foreach ($items as $item)
                                @php
                                    $label      = $item[0];
                                    $routeName  = $item[1];
                                    $pattern    = $item[2];
                                    $icon       = $item[3];
                                    $permission = $item[4] ?? null;
                                @endphp

                                @if (!$permission || auth()->user()->can($permission))
                                    @php $active = request()->routeIs($pattern); @endphp
                                    <a href="{{ route($routeName) }}"
                                       @if ($active) aria-current="page" @endif
                                       class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors
                                              {{ $active ? 'bg-white/10 text-white font-medium' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}
                                              focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                                        @if ($active)
                                            <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r bg-amber-500"></span>
                                        @endif
                                        <svg class="h-5 w-5 flex-shrink-0 {{ $active ? 'text-amber-400' : 'text-slate-400 group-hover:text-slate-200' }}"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                                        </svg>
                                        <span class="truncate">{{ $label }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <!-- ผู้ใช้ + ออกจากระบบ -->
        <div class="p-3 border-t border-white/10">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 p-2.5">
                <div class="h-9 w-9 flex-shrink-0 rounded-full bg-ink-700 text-white flex items-center justify-center text-sm font-semibold notranslate" translate="no">{{ $initial }}</div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-white truncate notranslate" translate="no">{{ $userName }}</div>
                    <div class="text-xs text-slate-400 truncate notranslate" translate="no">{{ Auth::user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" title="ออกจากระบบ" aria-label="ออกจากระบบ"
                            class="rounded-lg p-2 text-slate-400 hover:bg-red-500 hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 flex flex-col overflow-hidden print:block print:overflow-visible">

        <!-- Header (ซ่อนเวลาพิมพ์) -->
        <header class="h-16 flex-shrink-0 bg-white/90 backdrop-blur border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 print:hidden">
            <button type="button" onclick="toggleSidebar()" class="-ml-2 p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 shrink-0" aria-label="เปิด/ซ่อนเมนู" title="เปิด/ซ่อนเมนู">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            <span class="h-6 w-px bg-slate-200 shrink-0 hidden sm:block"></span>

            <h2 class="text-lg sm:text-xl font-semibold text-slate-900 tracking-tight truncate shrink-0 max-w-[9rem] sm:max-w-xs">@yield('title', 'Dashboard')</h2>

            {{-- แถบปุ่มลัด: เลื่อนซ้ายขวาได้ (ล้อเมาส์ / ลากนิ้ว / แถบเลื่อนของทัชแพด) --}}
            <div id="header-actions" class="header-scroll min-w-0 flex-1 overflow-x-auto overscroll-x-contain">
                <div class="ml-auto flex w-max items-center gap-2 px-1 py-1">
                    @yield('header_actions')
                </div>
            </div>

            <!-- ตัวสลับภาษา: อยู่นอกกล่องเลื่อน เพื่อให้ dropdown ไม่โดนตัด -->
            <div class="notranslate relative z-50 shrink-0" translate="no"
                 x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">

                <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                        aria-label="เปลี่ยนภาษา" title="เปลี่ยนภาษา"
                        class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-2.5 text-sm text-slate-700
                               hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition">
                    <img id="lang-current-flag" src="https://flagcdn.com/w40/th.png" alt="" class="h-6 w-6 rounded-full object-cover ring-1 ring-slate-200">
                    <span id="lang-current-code" class="hidden sm:inline font-medium">TH</span>
                    <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                     class="absolute right-0 top-full mt-2 w-48 overflow-hidden rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-slate-200">
                    @foreach ($languages as [$code, $name, $flag, $short])
                        <button type="button" data-lang="{{ $code }}" data-flag="{{ $flag }}" data-short="{{ $short }}"
                                @click="open = false"
                                onclick="setLanguage('{{ $code }}')"
                                class="lang-item flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 transition-colors">
                            <img src="https://flagcdn.com/w40/{{ $flag }}.png" alt="" class="h-5 w-5 rounded-full object-cover ring-1 ring-slate-200">
                            <span class="flex-1 text-left">{{ $name }}</span>
                            <svg class="lang-check hidden h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- widget จริงของ Google (ซ่อนไว้นอกจอ ไม่ใช้ display:none เพราะบางครั้ง widget จะไม่ถูกสร้าง) -->
            <div id="google_translate_element" style="position:absolute;left:-9999px;top:0" aria-hidden="true"></div>

            <!-- เมนูผู้ใช้ -->
            <div class="relative z-50 hidden sm:block shrink-0 pl-3 border-l border-slate-200"
                 x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                        class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition-colors"
                        :class="open && 'bg-slate-100'">
                    <span class="h-8 w-8 rounded-full bg-ink-800 text-white flex items-center justify-center text-sm font-semibold notranslate" translate="no">{{ $initial }}</span>
                    <span class="text-sm font-medium text-slate-700 max-w-[10rem] truncate notranslate" translate="no">{{ $userName }}</span>
                    <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                     class="absolute right-0 top-full mt-2 w-64 overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">
                    <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                        <span class="h-10 w-10 flex-shrink-0 rounded-full bg-ink-800 text-white flex items-center justify-center font-semibold notranslate" translate="no">{{ $initial }}</span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-slate-900 notranslate" translate="no">{{ $userName }}</div>
                            <div class="truncate text-xs text-slate-500 notranslate" translate="no">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <div class="p-1.5">
                        <a href="{{ $profileUrl }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            ข้อมูลโปรไฟล์
                        </a>
                    </div>

                    <div class="border-t border-slate-100 p-1.5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                ออกจากระบบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area (แสดงเต็มหน้าเวลาพิมพ์) -->
        <main id="main-content" class="main-scroll flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 lg:p-8 print:p-0 print:bg-white print:overflow-visible">

            <!-- แจ้งเตือนจากระบบ -->
            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition.opacity.duration.300ms
                     class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden" role="status">
                    <svg class="h-5 w-5 mt-0.5 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button type="button" @click="show = false" class="-m-1 rounded p-1 text-emerald-600 hover:bg-emerald-100" aria-label="ปิดข้อความ">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.300ms
                     class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 print:hidden" role="alert">
                    <svg class="h-5 w-5 mt-0.5 flex-shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button type="button" @click="show = false" class="-m-1 rounded p-1 text-red-600 hover:bg-red-100" aria-label="ปิดข้อความ">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 print:hidden" role="alert">
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
            const closed = document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.toggle('opacity-0', closed);
            document.getElementById('sidebar-overlay').classList.toggle('pointer-events-none', closed);
        }

        // มือถือ: กด ESC เพื่อปิดเมนู
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            const sb = document.getElementById('sidebar');
            if (!window.matchMedia('(min-width: 1024px)').matches && !sb.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });

        // แถบปุ่มลัดบน header: หมุนล้อเมาส์ = เลื่อนซ้ายขวา
        (function () {
            const el = document.getElementById('header-actions');
            if (!el) return;
            el.addEventListener('wheel', function (e) {
                if (el.scrollWidth <= el.clientWidth) return;
                if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                    e.preventDefault();
                    el.scrollLeft += e.deltaY;
                }
            }, { passive: false });
        })();
    </script>

    <!-- ===== สลับภาษาด้วย Google Translate ===== -->
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'th',
                includedLanguages: 'th,en,zh-CN,ja,ko',
                autoDisplay: false
            }, 'google_translate_element');
        }

        function getCurrentLang() {
            const m = document.cookie.match(/(?:^|;\s*)googtrans=\/[^/]+\/([^;]+)/);
            return m ? decodeURIComponent(m[1]) : 'th';
        }

        // เขียน/ลบ cookie googtrans (ใส่ domain เฉพาะเมื่อเป็นโดเมนจริง ไม่ใช่ localhost หรือ IP)
        function writeGoogTrans(value) {
            const host = location.hostname;
            const expire = value ? '' : '; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            const base = 'googtrans=' + value + expire + '; path=/';
            document.cookie = base;
            if (host.includes('.') && !/^\d+(\.\d+){3}$/.test(host)) {
                document.cookie = base + '; domain=' + host;
                document.cookie = base + '; domain=.' + host;
            }
        }

        // อัปเดตธง ตัวย่อภาษา และเครื่องหมายถูกของรายการที่เลือก
        function markCurrent(lang) {
            document.querySelectorAll('.lang-item').forEach(function (btn) {
                const on = btn.dataset.lang === lang;
                btn.classList.toggle('bg-amber-50', on);
                btn.classList.toggle('font-medium', on);
                btn.querySelector('.lang-check').classList.toggle('hidden', !on);
                if (on) {
                    document.getElementById('lang-current-flag').src =
                        'https://flagcdn.com/w40/' + btn.dataset.flag + '.png';
                    document.getElementById('lang-current-code').textContent = btn.dataset.short;
                }
            });
        }

        function setLanguage(lang) {
            if (lang === getCurrentLang()) return;

            // กลับเป็นภาษาไทย: ลบ cookie แล้วโหลดหน้าใหม่ (วิธีที่เสถียรที่สุด)
            if (lang === 'th') {
                writeGoogTrans('');
                location.reload();
                return;
            }

            // ภาษาอื่น: เซ็ต cookie ไว้ให้หน้าถัดไปจำค่า
            writeGoogTrans('/th/' + lang);

            // ถ้า widget ของ Google โหลดเสร็จแล้ว สั่งเปลี่ยนภาษาทันที ไม่ต้องรีโหลด
            const combo = document.querySelector('select.goog-te-combo');
            if (combo) {
                combo.value = lang;
                combo.dispatchEvent(new Event('change'));
                markCurrent(lang);
            } else {
                // widget ยังไม่พร้อม: รีโหลด แล้ว Google จะอ่าน cookie แปลให้เอง
                location.reload();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            markCurrent(getCurrentLang());
        });
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    @stack('scripts')
</body>
</html>
