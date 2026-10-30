@php
    // ชื่อและโลโก้ร้านจากหน้า Settings (ใช้ชุดเดียวกับเมนูด้านข้างในระบบ)
    $companyName = rescue(fn () => \App\Models\Setting::get('company_name', 'Mini ERP'), 'Mini ERP');
    $companyLogo = rescue(fn () => \App\Models\Setting::get('company_logo'), null);

    // เมนูตัวอย่างในภาพหน้าจอ (ชื่อตรงกับเมนูจริงใน config/nav.php)
    $mockMenu = [
        'การขาย'     => ['ขายหน้าร้าน (POS)', 'ใบเสนอราคา', 'รับชำระเงิน'],
        'คลังสินค้า'  => ['สินค้า', 'ปรับปรุงสต็อก'],
        'รายงาน'     => ['รายงานภาพรวม', 'รายงานลูกหนี้ (A/R)'],
    ];

    // บิลตัวอย่าง (คำนวณตรงกัน: 1,780 + 185 + 480 = 2,445 − 45 = 2,400)
    $mockLines = [
        ['ผ้าเบรกหน้า Hilux Vigo', 2, 890],
        ['กรองน้ำมันเครื่อง', 1, 185],
        ['หัวเทียน Iridium', 4, 120],
    ];

    // ขั้นตอนการทำงานของร้านในแต่ละวัน (เป็นลำดับจริง จึงใช้เลขกำกับ)
    $steps = [
        ['รับของเข้า',  'ใบสั่งซื้อ (PO)',        'กดรับของ สต็อกเพิ่มและต้นทุนล่าสุดอัปเดตให้เอง'],
        ['ขายหน้าร้าน', 'POS',                   'ตัดสต็อกทันที รับเงินสด โอน บัตร หรือขายเชื่อ'],
        ['รับชำระหนี้', 'รับชำระเงิน',            'ออกใบรับเงินให้ลูกค้าเครดิต และดูยอดค้างตามอายุหนี้'],
        ['ปิดกะ',       'เปิด-ปิดกะ',            'นับเงินในลิ้นชัก เทียบกับยอดที่ระบบคำนวณ'],
        ['ดูรายงาน',    'รายงาน',                'ยอดขาย กำไรขั้นต้น ลูกหนี้ และเจ้าหนี้'],
    ];

    // แต่ละตำแหน่งเห็นเมนูอะไรบ้าง (ตรงกับสิทธิ์ใน RoleAndPermissionSeeder)
    $roles = [
        ['ผู้จัดการ',      'ทุกเมนู รวมถึงยกเลิกบิล จัดการผู้ใช้ และตั้งค่าระบบ'],
        ['พนักงานขาย',    'POS, ใบเสนอราคา, รับชำระเงิน, เปิด-ปิดกะ, ลูกค้า'],
        ['พนักงานคลัง',   'ใบรับ/ใบเบิกสินค้า, ใบสั่งซื้อ, ปรับปรุงสต็อก'],
        ['พนักงานบัญชี',  'รับชำระเงิน, ค่าใช้จ่าย, รายงานทั้งหมด'],
        ['พนักงานขนส่ง',  'ดูสต็อกและประวัติการขาย'],
    ];
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $companyName }} | ระบบบริหารร้านอะไหล่</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- ใช้ Tailwind + สีชุดเดียวกับ layouts/app.blade.php เพื่อให้หน้าแรกกับระบบหน้าตาเหมือนกัน --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"IBM Plex Sans Thai"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        ink: { 700: '#24385a', 800: '#1a2a47', 900: '#122038', 950: '#0c1729' },
                        amber: { 400: '#f5b04c', 500: '#f2a02b' },
                    },
                },
            },
        }
    </script>
    <style>body { font-feature-settings: "tnum"; }</style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">

    {{-- ===== ส่วนหัว + Hero (พื้นสีเดียวกับเมนูด้านข้างของระบบ) ===== --}}
    <div class="bg-ink-950 text-slate-300">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 sm:px-8">
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                @if ($companyLogo)
                    <img src="{{ asset('storage/logo/' . $companyLogo) }}" alt="" class="h-9 w-9 rounded-lg object-contain">
                @else
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-500 text-lg font-bold text-ink-950">{{ mb_substr($companyName, 0, 1) }}</span>
                @endif
                <span class="truncate text-base font-semibold tracking-tight text-white">{{ $companyName }}</span>
            </a>

            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่แดชบอร์ด</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">เข้าสู่ระบบ</a>
                @endauth
            @endif
        </header>

        <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pb-20 pt-10 sm:px-8 lg:grid-cols-[1fr_1.1fr] lg:pb-24 lg:pt-14">
            <div>
                <h1 class="text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl">
                    จัดการร้านอะไหล่<br>ตั้งแต่รับของเข้า จนถึงปิดกะ
                </h1>
                <p class="mt-6 max-w-md text-base leading-relaxed text-slate-300">
                    ขายหน้าร้าน ตัดสต็อก รับชำระหนี้ และดูกำไรขั้นต้นได้ในที่เดียว ไม่ต้องจดซ้ำสองที่
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-lg bg-amber-500 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่แดชบอร์ด</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg bg-amber-500 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่ระบบ</a>
                        @endauth
                    @endif
                    <a href="#workflow" class="rounded-lg border border-white/20 px-6 py-3 text-sm font-medium text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">ดูขั้นตอนการทำงาน</a>
                </div>
            </div>

            {{-- ภาพจำลองหน้า POS ใช้โครงเดียวกับระบบจริง: เมนูสีน้ำเงินเข้ม + แถบสีเหลืองอำพันที่เมนูที่เลือก --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-white/10" aria-label="ตัวอย่างหน้าขายหน้าร้าน" role="img">
                <div class="flex">
                    <div class="hidden w-48 flex-shrink-0 bg-ink-900 p-3 text-xs sm:block">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-500 text-sm font-bold text-ink-950">{{ mb_substr($companyName, 0, 1) }}</span>
                            <span class="truncate font-semibold text-white">{{ $companyName }}</span>
                        </div>
                        @foreach ($mockMenu as $group => $items)
                            <div class="mb-1 mt-3 px-2 text-slate-500">{{ $group }}</div>
                            @foreach ($items as $item)
                                @php $active = $loop->parent->first && $loop->first; @endphp
                                <div class="relative rounded-md px-2 py-1.5 {{ $active ? 'bg-white/10 font-medium text-white' : 'text-slate-300' }}">
                                    @if ($active)<span class="absolute bottom-1.5 left-0 top-1.5 w-1 rounded-r bg-amber-500"></span>@endif
                                    {{ $item }}
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    <div class="min-w-0 flex-1 bg-slate-50 p-4 sm:p-5">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span class="font-semibold text-slate-800">INV-20261030-000128</span>
                            <span>ตัวอย่างบิล</span>
                        </div>
                        <div class="mt-3 rounded-xl bg-white p-4 text-sm ring-1 ring-slate-200">
                            @foreach ($mockLines as [$name, $qty, $price])
                                <div class="flex items-baseline justify-between gap-3 py-1.5 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                                    <div class="min-w-0">
                                        <div class="truncate text-slate-800">{{ $name }}</div>
                                        <div class="text-xs text-slate-500">{{ $qty }} × ฿{{ number_format($price, 2) }}</div>
                                    </div>
                                    <div class="text-slate-800">฿{{ number_format($qty * $price, 2) }}</div>
                                </div>
                            @endforeach
                            <div class="mt-2 space-y-1 border-t border-slate-200 pt-3 text-xs text-slate-500">
                                <div class="flex justify-between"><span>รวมสินค้า</span><span>฿2,445.00</span></div>
                                <div class="flex justify-between"><span>ส่วนลด</span><span>−฿45.00</span></div>
                            </div>
                            <div class="mt-2 flex items-center justify-between rounded-lg bg-ink-900 px-4 py-3 text-white">
                                <span class="text-sm">ยอดสุทธิ</span>
                                <span class="text-xl font-semibold"><span class="text-amber-400">฿</span>2,400.00</span>
                            </div>
                            <div class="mt-2 flex justify-between text-xs text-slate-500">
                                <span>เงินสด รับ ฿2,500.00</span><span>ทอน ฿100.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <main>
        {{-- ===== ลำดับงานของร้าน ===== --}}
        <section id="workflow" class="mx-auto max-w-6xl scroll-mt-6 px-5 py-16 sm:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">หนึ่งวันของร้าน ผ่านระบบเดียว</h2>
            <p class="mt-2 max-w-xl text-slate-600">ทุกขั้นตอนเชื่อมกัน ของที่รับเข้าไปแล้วขายได้ทันที ยอดขายไหลเข้ารายงานโดยไม่ต้องคีย์ซ้ำ</p>

            <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($steps as [$title, $menu, $desc])
                    <li class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-900 text-sm font-semibold text-amber-400">{{ $loop->iteration }}</span>
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-xs font-medium text-amber-500">เมนู: {{ $menu }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ===== สิทธิ์ตามตำแหน่ง ===== --}}
        <section class="mx-auto max-w-6xl px-5 pb-16 sm:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_1.4fr] lg:gap-14">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">แต่ละคนเห็นเฉพาะงานของตัวเอง</h2>
                    <p class="mt-2 text-slate-600">ผู้จัดการกำหนดตำแหน่งให้พนักงานแต่ละคน เมนูที่ไม่เกี่ยวกับงานจะไม่แสดง และเข้าตรงๆ ไม่ได้ รองรับการยืนยันตัวตนสองขั้นตอนด้วย Google Authenticator</p>
                </div>

                <dl class="divide-y divide-slate-200 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    @foreach ($roles as [$role, $can])
                        <div class="grid gap-1 px-5 py-4 sm:grid-cols-[9rem_1fr] sm:gap-4">
                            <dt class="font-semibold text-slate-900">{{ $role }}</dt>
                            <dd class="text-sm leading-relaxed text-slate-600">{{ $can }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- ===== เข้าสู่ระบบ ===== --}}
        <section class="mx-auto max-w-6xl px-5 pb-16 sm:px-8">
            <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-ink-900 p-8 sm:flex-row sm:items-center sm:p-10">
                <div>
                    <h2 class="text-xl font-bold text-white sm:text-2xl">พร้อมเริ่มงานวันนี้</h2>
                    <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-300">บัญชีผู้ใช้สร้างโดยผู้ดูแลระบบของร้าน ถ้ายังไม่มีบัญชี ให้แจ้งผู้จัดการให้เพิ่มให้</p>
                </div>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="flex-shrink-0 rounded-lg bg-amber-500 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่แดชบอร์ด</a>
                    @else
                        <a href="{{ route('login') }}" class="flex-shrink-0 rounded-lg bg-amber-500 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่ระบบ</a>
                    @endauth
                @endif
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-5 py-6 text-sm text-slate-500 sm:px-8">&copy; {{ date('Y') }} {{ $companyName }}</div>
    </footer>

</body>
</html>
