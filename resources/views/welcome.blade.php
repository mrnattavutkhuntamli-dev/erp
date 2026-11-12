@php

    // ชื่อและโลโก้ร้านจากหน้า Settings

    $companyName = rescue(fn () => \App\Models\Setting::get('company_name', 'Mini ERP'), 'Mini ERP');

    $companyLogo = rescue(fn () => \App\Models\Setting::get('company_logo'), null);

    $currentUrl = url()->current();

    $siteDescription = 'ระบบบริหารร้านอะไหล่ครบวงจร จัดการขายหน้าร้าน (POS) ตัดสต็อก รับชำระหนี้ และดูรายงานกำไรขั้นต้นได้ในที่เดียว';

    $logoUrl = $companyLogo ? asset('storage/logo/' . $companyLogo) : null;

    // JSON-LD สร้างด้วย PHP เพื่อไม่ให้ Blade ตีความตัวอักษรพิเศษ

    $publisher = ['@type' => 'Organization', 'name' => $companyName, 'url' => url('/')];

    if ($logoUrl) {

        $publisher['logo'] = $logoUrl;

    }

    $jsonLd = [

        '@context'            => 'https://schema.org',

        '@type'               => 'SoftwareApplication',

        'name'                => $companyName . ' - ระบบบริหารร้านอะไหล่',

        'operatingSystem'     => 'Web-based',

        'applicationCategory' => 'BusinessApplication',

        'description'         => $siteDescription,

        'url'                 => $currentUrl,

        'publisher'           => $publisher,

    ];

    // เมนูตัวอย่างในภาพหน้าจอ

    $mockMenu = [

        'การขาย'     => ['ขายหน้าร้าน (POS)', 'ใบเสนอราคา', 'รับชำระเงิน'],

        'คลังสินค้า'  => ['สินค้า', 'ปรับปรุงสต็อก'],

        'รายงาน'     => ['รายงานภาพรวม', 'รายงานลูกหนี้ (A/R)'],

    ];

    // บิลตัวอย่าง: 1,780 + 185 + 480 = 2,445 ลบส่วนลด 45 = 2,400

    $mockLines = [

        ['ผ้าเบรกหน้า Hilux Vigo', 2, 890],

        ['กรองน้ำมันเครื่อง', 1, 185],

        ['หัวเทียน Iridium', 4, 120],

    ];

    // โมดูลหลักของระบบ (อิงจาก routes จริง)

    $modules = [

        ['ขายหน้าร้าน (POS)', 'ขาย รับเงิน ทอนเงิน และตัดสต็อกในขั้นตอนเดียว', [

            'ขายเงินสด โอน บัตร หรือขายเชื่อ',

            'ประวัติการขายย้อนหลัง ดูรายละเอียดทุกบิล',

            'คืนสินค้าด้วยใบรับคืน แยกสิทธิ์จากการยกเลิกบิล',

            'ยกเลิกบิลได้เฉพาะผู้มีสิทธิ์',

        ]],

        ['ใบเสนอราคา', 'เสนอราคาให้ลูกค้าก่อน แล้วต่อยอดเป็นบิลขายได้เลย', [

            'สร้างและติดตามสถานะใบเสนอราคา',

            'แปลงใบเสนอราคาเป็นบิลขายในคลิกเดียว',

            'ส่งออกเป็น PDF ส่งให้ลูกค้า',

        ]],

        ['ใบรับเงินและลูกหนี้', 'ตามหนี้ลูกค้าเครดิต ออกใบรับเงินได้จากบิลขาย', [

            'ออกใบรับเงินจากบิลขายที่ยังค้างชำระ',

            'ยกเลิกใบรับเงิน (เฉพาะผู้จัดการและบัญชี)',

            'รายงานลูกหนี้ตามอายุหนี้ ส่งออก PDF',

        ]],

        ['สินค้าและข้อมูลหลัก', 'จัดระเบียบสินค้าให้ค้นหาง่าย ตรงกับรถที่ลูกค้าใช้', [

            'เพิ่ม แก้ไข เปิด-ปิดใช้งานสินค้า',

            'หมวดหมู่ ยี่ห้อสินค้า และหน่วยนับ',

            'ยี่ห้อรถและรุ่นรถ สำหรับอะไหล่เฉพาะรุ่น',

        ]],

        ['คลังสินค้า', 'ทุกการเข้าออกของสต็อกมีเอกสารและประวัติให้ตรวจสอบ', [

            'ใบรับและใบเบิกสินค้า แก้ไขหรือยกเลิกได้',

            'ปรับปรุงสต็อกเมื่อนับของจริงไม่ตรงระบบ',

            'รายงานความเคลื่อนไหวสต็อกรายสินค้า',

        ]],

        ['จัดซื้อและเจ้าหนี้', 'ตั้งแต่สั่งของ รับของ จนถึงจ่ายเงินซัพพลายเออร์', [

            'ใบสั่งซื้อ (PO) กดรับของแล้วสต็อกเพิ่ม',

            'บันทึกการจ่ายเงินให้ซัพพลายเออร์ ตัดยอดเจ้าหนี้',

            'ยกเลิกรายการจ่ายที่บันทึกผิด',

            'รายงานเจ้าหนี้ตามอายุหนี้ ส่งออก PDF',

        ]],

        ['กะและพนักงาน', 'ควบคุมเงินในลิ้นชักและวางตารางกะให้ชัดเจน', [

            'เปิด-ปิดกะ เทียบเงินที่นับกับยอดระบบ',

            'ตารางกะพนักงานแบบปฏิทิน',

            'เปิด-ปิดบัญชีผู้ใช้ และรีเซ็ต 2FA เมื่อมือถือหาย',

        ]],

        ['ค่าใช้จ่ายและรายงาน', 'เห็นภาพรวมของร้านทั้งรายรับและรายจ่าย', [

            'บันทึกค่าใช้จ่ายของร้าน',

            'รายงานภาพรวมยอดขายและกำไรขั้นต้น ส่งออก PDF',

            'ตั้งค่าชื่อและโลโก้ร้านจากหน้าตั้งค่าระบบ',

        ]],

    ];

    // เอกสารที่เชื่อมต่อกัน

    $flows = [

        ['ฝั่งขาย',       ['ใบเสนอราคา', 'บิลขาย', 'ใบรับเงิน']],

        ['ฝั่งซื้อ',       ['ใบสั่งซื้อ (PO)', 'รับของเข้าสต็อก', 'จ่ายเงินซัพพลายเออร์']],

        ['เมื่อลูกค้าคืนของ', ['บิลขาย', 'ใบรับคืน', 'สต็อกกลับเข้าคลัง']],

    ];

    // ขั้นตอนงานประจำวัน

    $steps = [

        ['รับของเข้า',  'ใบสั่งซื้อ (PO)',  'กดรับของ สต็อกเพิ่มและต้นทุนล่าสุดอัปเดตให้เอง'],

        ['ขายหน้าร้าน', 'POS',             'ตัดสต็อกทันที รับเงินสด โอน บัตร หรือขายเชื่อ'],

        ['รับชำระหนี้', 'รับชำระเงิน',      'ออกใบรับเงินให้ลูกค้าเครดิต และดูยอดค้างตามอายุหนี้'],

        ['ปิดกะ',       'เปิด-ปิดกะ',      'นับเงินในลิ้นชัก เทียบกับยอดที่ระบบคำนวณ'],

        ['ดูรายงาน',    'รายงาน',          'ยอดขาย กำไรขั้นต้น ลูกหนี้ และเจ้าหนี้'],

    ];

    // ตัวอย่างความเคลื่อนไหวสต็อก: ยอดคงเหลือคำนวณสะสมจากยอดตั้งต้น

    $stockStart = 10;

    $mockMovements = [

        ['รับเข้า',   'ใบสั่งซื้อ PO-0042',   20],

        ['ขาย',       'บิล INV-000128',       -2],

        ['คืนสินค้า', 'ใบรับคืน SR-0007',      1],

        ['ปรับปรุง',  'นับสต็อกไม่ตรง',        -1],

    ];

    $runningStock = $stockStart;

    foreach ($mockMovements as $i => $row) {

        $runningStock += $row[2];

        $mockMovements[$i][3] = $runningStock;

    }

    // ตัวอย่างลูกหนี้ตามอายุหนี้

    $agingHeads = ['0-30 วัน', '31-60 วัน', '61-90 วัน', 'เกิน 90 วัน'];

    $mockAging = [

        ['อู่ช่างสมชาย',        [12400, 3200, 0, 0]],

        ['อู่ศรีสยามการช่าง',   [8900, 0, 4500, 0]],

        ['บริษัท ขนส่งภาคใต้',  [0, 6800, 0, 2100]],

    ];

    $agingTotals = [0, 0, 0, 0];

    foreach ($mockAging as [, $cells]) {

        foreach ($cells as $i => $v) {

            $agingTotals[$i] += $v;

        }

    }

    // ตัวอย่างรายงานรายสัปดาห์

    $mockWeek = [

        ['จ.', 18400, 5150], ['อ.', 21150, 6000], ['พ.', 16900, 4700], ['พฤ.', 24300, 6800],

        ['ศ.', 27800, 7850], ['ส.', 31200, 8700], ['อา.', 12600, 3500],

    ];

    $weekSales = array_sum(array_column($mockWeek, 1));

    $weekProfit = array_sum(array_column($mockWeek, 2));

    $weekMax = max(array_column($mockWeek, 1));

    // สิทธิ์ตามตำแหน่ง

    $roles = [

        ['ผู้จัดการ',      'ทุกเมนู รวมถึงยกเลิกบิล จัดการผู้ใช้ และตั้งค่าระบบ'],

        ['พนักงานขาย',    'POS, ใบเสนอราคา, รับชำระเงิน, เปิด-ปิดกะ, ลูกค้า'],

        ['พนักงานคลัง',   'ใบรับ/ใบเบิกสินค้า, ใบสั่งซื้อ, ปรับปรุงสต็อก'],

        ['พนักงานบัญชี',  'รับชำระเงิน, ค่าใช้จ่าย, รายงานทั้งหมด'],

        ['พนักงานขนส่ง',  'ดูสต็อกและประวัติการขาย'],

    ];

    // สิทธิ์แยกรายการ (ตรงกับชื่อสิทธิ์ใน routes)

    $permissions = [

        'สินค้า'   => ['ดูสินค้า', 'เพิ่มสินค้า', 'แก้ไขสินค้า', 'ลบและปิดใช้งานสินค้า', 'จัดการข้อมูลหลัก'],

        'คลัง'     => ['ดูคลังสินค้า', 'สร้างใบรับ/ใบเบิก', 'ยกเลิกใบรับ/ใบเบิก', 'ปรับปรุงสต็อก'],

        'ขาย'      => ['ขายหน้าร้าน', 'ดูประวัติการขาย', 'ยกเลิกบิลขาย', 'คืนสินค้า', 'จัดการใบรับเงิน', 'ยกเลิกใบรับเงิน'],

        'คู่ค้า'    => ['จัดการลูกค้า', 'จัดการซัพพลายเออร์'],

        'บัญชีและระบบ' => ['จัดการค่าใช้จ่าย', 'ดูรายงาน', 'จัดการผู้ใช้และตั้งค่าระบบ'],

    ];

    $permissionCount = 0;

    foreach ($permissions as $group) {

        $permissionCount += count($group);

    }

    // ตัวเลขสรุปใต้ Hero (นับจากข้อมูลจริงในหน้านี้)

    $stats = [

        [count($modules),   'โมดูลงานครบตั้งแต่ซื้อ ขาย คลัง บัญชี'],

        [count($roles),     'ตำแหน่งพนักงาน แยกสิทธิ์ชัดเจน'],

        [$permissionCount,  'สิทธิ์ย่อย กำหนดได้ละเอียดระดับงาน'],

        ['2FA',             'ยืนยันตัวตนสองขั้นตอน'],

    ];

    // ความปลอดภัย

    $security = [

        ['เมนูที่ไม่เกี่ยวกับงานจะไม่แสดง', 'พนักงานเห็นเฉพาะเมนูตามตำแหน่งของตัวเอง'],

        ['พิมพ์ลิงก์เข้าตรงๆ ก็ไม่ได้',      'ระบบตรวจสิทธิ์ทุกหน้า ไม่ใช่แค่ซ่อนเมนู'],

        ['ยืนยันตัวตนสองขั้นตอน',            'รองรับ Google Authenticator และผู้จัดการรีเซ็ตให้ได้เมื่อมือถือหาย'],

        ['ผู้ดูแลระบบเป็นคนสร้างบัญชี',       'ไม่มีการสมัครเองจากหน้าเว็บ ปิดบัญชีพนักงานที่ลาออกได้ทันที'],

        ['การยกเลิกเอกสารมีสิทธิ์แยก',        'ยกเลิกบิล ใบรับเงิน และเอกสารสต็อก แยกจากสิทธิ์สร้างเอกสาร'],

    ];

    // เทคโนโลยีที่ใช้

    $tech = [

        ['Laravel 12',  'เฟรมเวิร์กหลังบ้าน'],

        ['PHP 8.2',     'ภาษาฝั่งเซิร์ฟเวอร์'],

        ['MySQL',       'ฐานข้อมูล'],

        ['Tailwind CSS', 'ออกแบบหน้าจอ'],

        ['Role และ Permission', 'ควบคุมสิทธิ์ด้วย middleware'],

        ['2FA (TOTP)',  'ยืนยันตัวตนสองขั้นตอน'],

    ];

    // คำถามที่พบบ่อย

    $faqs = [

        ['ถ้ายังไม่มีบัญชี ต้องทำอย่างไร?', 'บัญชีผู้ใช้สร้างโดยผู้ดูแลระบบของร้าน ให้แจ้งผู้จัดการให้เพิ่มบัญชีและกำหนดตำแหน่งให้'],

        ['ขายเชื่อให้ลูกค้าประจำได้ไหม?', 'ได้ ยอดจะไปอยู่ในรายงานลูกหนี้ และออกใบรับเงินเมื่อลูกค้ามาชำระภายหลัง'],

        ['ลูกค้าขอใบเสนอราคาก่อนซื้อ ต้องทำใหม่ตอนขายไหม?', 'ไม่ต้อง แปลงใบเสนอราคาเป็นบิลขายได้โดยตรง และส่งออกใบเสนอราคาเป็น PDF ให้ลูกค้าได้'],

        ['ลูกค้านำของมาคืน ทำอย่างไร?', 'เปิดบิลเดิมแล้วออกใบรับคืน สต็อกจะกลับเข้าคลัง พนักงานขายคืนสินค้าได้ แต่ยกเลิกบิลต้องเป็นผู้มีสิทธิ์'],

        ['ต้นทุนสินค้าอัปเดตเมื่อไหร่?', 'เมื่อกดรับของเข้าจากใบสั่งซื้อ ระบบจะเพิ่มสต็อกและอัปเดตต้นทุนล่าสุดให้เอง'],

        ['นับเงินในลิ้นชักตรงกับระบบหรือไม่ รู้ได้อย่างไร?', 'ตอนปิดกะ ให้กรอกยอดเงินที่นับได้จริง ระบบจะเทียบกับยอดที่คำนวณจากบิลให้'],

        ['พนักงานทำมือถือที่ใช้ 2FA หาย ทำอย่างไร?', 'ผู้จัดการรีเซ็ต 2FA ของบัญชีนั้นได้จากหน้าจัดการผู้ใช้ แล้วให้พนักงานตั้งค่าใหม่'],

        ['เปลี่ยนชื่อและโลโก้ร้านได้ที่ไหน?', 'ที่หน้าตั้งค่าระบบ (เฉพาะผู้ที่มีสิทธิ์) ชื่อและโลโก้จะแสดงทั้งหน้านี้และในระบบ'],

    ];

@endphp

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $companyName }} | ระบบบริหารร้านอะไหล่ POS และ ERP</title>

    <meta name="description" content="{{ $siteDescription }}">

    <link rel="canonical" href="{{ $currentUrl }}">

    <meta property="og:type" content="website">

    <meta property="og:site_name" content="{{ $companyName }}">

    <meta property="og:title" content="{{ $companyName }} | ระบบบริหารร้านอะไหล่">

    <meta property="og:description" content="{{ $siteDescription }}">

    <meta property="og:url" content="{{ $currentUrl }}">

    @if ($logoUrl)

        <meta property="og:image" content="{{ $logoUrl }}">

    @endif

    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind และสีชุดเดียวกับ layouts/app.blade.php --}}

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

    <style>

        html { scroll-behavior: smooth; }

        body { font-feature-settings: "tnum"; }

        details summary::-webkit-details-marker { display: none; }

        details[open] .faq-icon { transform: rotate(45deg); }


        :root {
            --erp-navy: #0b1730;
            --erp-navy-2: #122447;
            --erp-yellow: #ffb51b;
            --erp-page: #f3f6fb;
            --erp-ink: #15233d;
            --erp-muted: #71809a;
            --erp-line: #e7edf5;
        }

        html { scroll-behavior: smooth; scroll-padding-top: 24px; }
        body {
            background: var(--erp-page);
            color: var(--erp-ink);
            font-feature-settings: "tnum";
            -webkit-font-smoothing: antialiased;
        }

        /* Dark, premium hero */
        body > div.bg-ink-950 {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background:
                radial-gradient(ellipse at 78% 35%, rgba(40, 91, 164, .30), transparent 38%),
                radial-gradient(ellipse at 10% 95%, rgba(255, 181, 27, .08), transparent 34%),
                linear-gradient(125deg, #081329 0%, #0d1c39 52%, #101f3d 100%);
        }
        body > div.bg-ink-950::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            opacity: .22;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, black, transparent 90%);
        }
        body > div.bg-ink-950 header {
            min-height: 76px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        body > div.bg-ink-950 header nav a {
            position: relative;
            padding-block: 8px;
            color: #c6d0e1;
            transition: color .2s ease;
        }
        body > div.bg-ink-950 header nav a:hover { color: #fff; }
        body > div.bg-ink-950 header nav a::after {
            content: "";
            position: absolute;
            left: 0; right: 100%; bottom: 0;
            height: 2px;
            border-radius: 4px;
            background: var(--erp-yellow);
            transition: right .2s ease;
        }
        body > div.bg-ink-950 header nav a:hover::after { right: 0; }

        body > div.bg-ink-950 section h1 {
            letter-spacing: -.035em;
            text-wrap: balance;
        }
        body > div.bg-ink-950 section > div:first-child > p:first-child {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border: 1px solid rgba(255,181,27,.24);
            border-radius: 999px;
            background: rgba(255,181,27,.08);
        }
        body > div.bg-ink-950 section > div:first-child > p:first-child::before {
            content: "";
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--erp-yellow);
            box-shadow: 0 0 12px rgba(255,181,27,.8);
        }
        body > div.bg-ink-950 section .bg-amber-500,
        body > div.bg-ink-950 a.bg-amber-500 {
            box-shadow: 0 8px 22px rgba(255,181,27,.18);
        }

        /* POS preview panel */
        body > div.bg-ink-950 section > .overflow-hidden.rounded-2xl {
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 22px;
            box-shadow: 0 28px 80px rgba(0,0,0,.38), 0 0 0 8px rgba(255,255,255,.025);
            transform: perspective(1200px) rotateY(-2deg);
            transition: transform .35s ease, box-shadow .35s ease;
        }
        body > div.bg-ink-950 section > .overflow-hidden.rounded-2xl:hover {
            transform: perspective(1200px) rotateY(0deg) translateY(-4px);
            box-shadow: 0 34px 90px rgba(0,0,0,.42);
        }
        body > div.bg-ink-950 .border-t.border-white\/10 {
            border-color: rgba(255,255,255,.10);
            background: rgba(3,10,25,.22);
        }
        body > div.bg-ink-950 dl > div dt {
            color: var(--erp-yellow);
            letter-spacing: -.04em;
        }
        body > div.bg-ink-950 dl > div dd { color: #c2cee0; }

        /* Consistent page rhythm and typography */
        main { background: var(--erp-page); }
        main section { scroll-margin-top: 28px; }
        main section h2 {
            color: #12213b;
            letter-spacing: -.025em;
            text-wrap: balance;
        }
        main section h2 + p { color: #6f7f98; }
        main section .rounded-2xl.bg-white,
        main section .rounded-xl.bg-white {
            border: 1px solid rgba(220,229,241,.9);
            box-shadow: 0 5px 18px rgba(21,42,77,.045);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }
        main section .rounded-2xl.bg-white:hover,
        main section .rounded-xl.bg-white:hover {
            transform: translateY(-3px);
            border-color: rgba(255,181,27,.55);
            box-shadow: 0 14px 30px rgba(21,42,77,.085);
        }
        #features .rounded-2xl.bg-white { min-height: 100%; }
        #features .rounded-2xl.bg-white > span:first-child {
            width: 38px;
            height: 4px;
            box-shadow: 0 0 14px rgba(255,181,27,.25);
        }
        #features .rounded-2xl.bg-white h3 { font-size: 1rem; }
        #features .rounded-2xl.bg-white ul { line-height: 1.65; }

        /* Workflow and document flow */
        #workflow ol > li { position: relative; overflow: hidden; }
        #workflow ol > li > span:first-child {
            box-shadow: 0 0 0 5px #eef2f8;
        }
        #workflow ol > li h3 { margin-top: 18px; }
        main section ol li,
        main section table { font-variant-numeric: tabular-nums; }
        main section table thead { background: #f7f9fc; }
        main section table th { color: #71809a; }
        main section table td { white-space: nowrap; }

        /* Report cards and bar chart */
        #reports dl > div {
            background: linear-gradient(145deg, #fff, #f8faff);
        }
        #reports .bg-ink-900 { background-color: #142746; }
        #reports .bg-amber-500 { background-color: var(--erp-yellow); }

        /* Security and permission chips */
        #security .rounded-2xl.bg-white { overflow: hidden; }
        #security .rounded-full { border: 1px solid #e8edf5; }

        /* FAQ */
        #faq .rounded-2xl.bg-white { box-shadow: 0 12px 32px rgba(21,42,77,.055); }
        #faq details { transition: background .2s ease; }
        #faq details:hover { background: #fbfcff; }
        #faq details[open] { background: #fffdf7; }
        #faq details[open] .faq-icon { color: #d98a00; }
        #faq summary { cursor: pointer; }

        footer { background: #fff; }

        @media (max-width: 1023px) {
            body > div.bg-ink-950 section > .overflow-hidden.rounded-2xl {
                transform: none;
            }
        }
        @media (max-width: 640px) {
            body > div.bg-ink-950 header { padding-block: 14px; }
            body > div.bg-ink-950 section h1 { font-size: clamp(2.1rem, 10vw, 3rem); }
            body > div.bg-ink-950 section { gap: 34px; }
            main section { padding-top: 52px; padding-bottom: 52px; }
            main section h2 { font-size: 1.65rem; line-height: 1.3; }
            #features .rounded-2xl.bg-white { padding: 18px; }
            main section .rounded-2xl.bg-white:hover,
            main section .rounded-xl.bg-white:hover { transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }

    </style>

</head>

<body class="bg-slate-100 font-sans text-slate-800 antialiased">

    {{-- ===== ส่วนหัว + Hero ===== --}}

    <div class="bg-ink-950 text-slate-300">

        <header class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5 sm:px-8">

            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">

                @if ($companyLogo)

                    <img src="{{ $logoUrl }}" alt="" class="h-9 w-9 rounded-lg object-contain">

                @else

                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-500 text-lg font-bold text-ink-950">{{ mb_substr($companyName, 0, 1) }}</span>

                @endif

                <span class="truncate text-base font-semibold tracking-tight text-white">{{ $companyName }}</span>

            </a>

            <nav class="hidden items-center gap-6 text-sm lg:flex" aria-label="เมนูหลัก">

                <a href="#features" class="transition hover:text-white">ฟีเจอร์</a>

                <a href="#workflow" class="transition hover:text-white">ขั้นตอนงาน</a>

                <a href="#reports" class="transition hover:text-white">รายงาน</a>

                <a href="#security" class="transition hover:text-white">สิทธิ์และความปลอดภัย</a>

                <a href="#tech" class="transition hover:text-white">เทคโนโลยี</a>

                <a href="#faq" class="transition hover:text-white">คำถามที่พบบ่อย</a>

            </nav>

            @if (Route::has('login'))

                @auth

                    <a href="{{ url('/dashboard') }}" class="flex-shrink-0 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่แดชบอร์ด</a>

                @else

                    <a href="{{ route('login') }}" class="flex-shrink-0 rounded-lg border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">เข้าสู่ระบบ</a>

                @endauth

            @endif

        </header>

        <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pb-16 pt-10 sm:px-8 lg:grid-cols-[1fr_1.1fr] lg:pb-20 lg:pt-14">

            <div>

                <p class="text-sm font-medium text-amber-400">ERP และ POS สำหรับร้านอะไหล่รถยนต์</p>

                <h1 class="mt-3 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl">

                    จัดการร้านอะไหล่<br>ตั้งแต่รับของเข้า จนถึงปิดกะ

                </h1>

                <p class="mt-6 max-w-md text-base leading-relaxed text-slate-300">

                    ขายหน้าร้าน ตัดสต็อก รับชำระหนี้ และดูกำไรขั้นต้นได้ในที่เดียว ไม่ต้องจดซ้ำสองที

                </p>

                <div class="mt-8 flex flex-wrap gap-3">

                    @if (Route::has('login'))

                        @auth

                            <a href="{{ url('/dashboard') }}" class="rounded-lg bg-amber-500 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่แดชบอร์ด</a>

                        @else

                            <a href="{{ route('login') }}" class="rounded-lg bg-amber-500 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">เข้าสู่ระบบ</a>

                        @endauth

                    @endif

                    <a href="#features" class="rounded-lg border border-white/20 px-6 py-3 text-sm font-medium text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">ดูฟีเจอร์ทั้งหมด</a>

                </div>

            </div>

            {{-- ภาพจำลองหน้า POS --}}

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

        {{-- แถบตัวเลขสรุป --}}

        <div class="border-t border-white/10">

            <dl class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-5 py-8 sm:px-8 lg:grid-cols-4">

                @foreach ($stats as [$num, $label])

                    <div>

                        <dt class="text-3xl font-bold text-amber-400">{{ $num }}</dt>

                        <dd class="mt-1 text-sm text-slate-300">{{ $label }}</dd>

                    </div>

                @endforeach

            </dl>

        </div>

    </div>

    <main>

        {{-- ===== ฟีเจอร์แยกตามโมดูล ===== --}}

        <section id="features" class="mx-auto max-w-6xl scroll-mt-6 px-5 pt-16 sm:px-8">

            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">ครบทุกงานที่ร้านอะไหล่ต้องใช้</h2>

            <p class="mt-2 max-w-xl text-slate-600">จากหน้าร้านถึงหลังบ้าน ข้อมูลชุดเดียวกันไหลต่อกันทุกเมนู</p>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                @foreach ($modules as [$title, $desc, $items])

                    <div class="flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                        <span class="block h-1 w-8 rounded-full bg-amber-500"></span>

                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>

                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>

                        <ul class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm text-slate-600">

                            @foreach ($items as $item)

                                <li class="flex gap-2">

                                    <span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>

                                    <span>{{ $item }}</span>

                                </li>

                            @endforeach

                        </ul>

                    </div>

                @endforeach

            </div>

        </section>

        {{-- ===== เอกสารที่เชื่อมต่อกัน ===== --}}

        <section class="mx-auto max-w-6xl px-5 py-16 sm:px-8">

            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">เอกสารทุกใบเชื่อมถึงกัน</h2>

            <p class="mt-2 max-w-xl text-slate-600">ไม่ต้องคีย์ข้อมูลซ้ำ เอกสารใบถัดไปต่อยอดจากใบก่อนหน้า</p>

            <div class="mt-8 space-y-4">

                @foreach ($flows as [$label, $chain])

                    <div class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:flex-row sm:items-center">

                        <div class="w-40 flex-shrink-0 text-sm font-semibold text-slate-900">{{ $label }}</div>

                        <ol class="flex flex-1 flex-wrap items-center gap-2 text-sm">

                            @foreach ($chain as $node)

                                <li class="rounded-lg bg-ink-900 px-3 py-1.5 font-medium text-white">{{ $node }}</li>

                                @if (! $loop->last)

                                    <li class="text-amber-500" aria-hidden="true">→</li>

                                @endif

                            @endforeach

                        </ol>

                    </div>

                @endforeach

            </div>

        </section>

        {{-- ===== ลำดับงานของร้าน ===== --}}

        <section id="workflow" class="mx-auto max-w-6xl scroll-mt-6 px-5 pb-16 sm:px-8">

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

        {{-- ===== ตัวอย่างหน้าจอ: ความเคลื่อนไหวสต็อกและลูกหนี้ ===== --}}

        <section class="mx-auto max-w-6xl px-5 pb-16 sm:px-8">

            <div class="grid gap-6 lg:grid-cols-2">

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">

                    <h2 class="text-xl font-bold text-slate-900">ตรวจสต็อกย้อนหลังได้ทุกรายการ</h2>

                    <p class="mt-1 text-sm text-slate-600">ทุกการรับ ขาย คืน และปรับปรุง มีบันทึก ดูได้ว่ายอดเปลี่ยนเพราะอะไร</p>

                    <p class="mt-4 text-sm font-medium text-slate-800">ผ้าเบรกหน้า Hilux Vigo <span class="font-normal text-slate-500">(ยอดตั้งต้น {{ $stockStart }})</span></p>

                    <div class="mt-2 overflow-x-auto">

                        <table class="w-full min-w-[22rem] text-left text-sm">

                            <thead class="text-xs text-slate-500">

                                <tr class="border-b border-slate-200">

                                    <th class="py-2 font-medium">ประเภท</th>

                                    <th class="py-2 font-medium">เอกสาร</th>

                                    <th class="py-2 text-right font-medium">จำนวน</th>

                                    <th class="py-2 text-right font-medium">คงเหลือ</th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($mockMovements as [$type, $doc, $qty, $balance])

                                    <tr class="{{ ! $loop->last ? 'border-b border-slate-100' : '' }}">

                                        <td class="py-2.5 text-slate-800">{{ $type }}</td>

                                        <td class="py-2.5 text-slate-500">{{ $doc }}</td>

                                        <td class="py-2.5 text-right font-medium {{ $qty > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $qty > 0 ? '+' : '−' }}{{ abs($qty) }}</td>

                                        <td class="py-2.5 text-right text-slate-900">{{ $balance }}</td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                    <p class="mt-3 text-xs text-slate-400">ข้อมูลตัวอย่างเพื่อแสดงหน้าตาของระบบ</p>

                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">

                    <h2 class="text-xl font-bold text-slate-900">ตามหนี้ได้ตรงจุด</h2>

                    <p class="mt-1 text-sm text-slate-600">รายงานลูกหนี้แยกตามอายุหนี้ เห็นทันทีว่าใครค้างนานแล้ว</p>

                    <div class="mt-5 overflow-x-auto">

                        <table class="w-full min-w-[26rem] text-left text-sm">

                            <thead class="text-xs text-slate-500">

                                <tr class="border-b border-slate-200">

                                    <th class="py-2 font-medium">ลูกค้า</th>

                                    @foreach ($agingHeads as $head)

                                        <th class="py-2 text-right font-medium">{{ $head }}</th>

                                    @endforeach

                                    <th class="py-2 text-right font-medium">รวม</th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($mockAging as [$customer, $cells])

                                    <tr class="border-b border-slate-100">

                                        <td class="py-2.5 text-slate-800">{{ $customer }}</td>

                                        @foreach ($cells as $v)

                                            <td class="py-2.5 text-right {{ $v === 0 ? 'text-slate-300' : '' }}">{{ $v === 0 ? '-' : number_format($v) }}</td>

                                        @endforeach

                                        <td class="py-2.5 text-right font-medium text-slate-900">{{ number_format(array_sum($cells)) }}</td>

                                    </tr>

                                @endforeach

                            </tbody>

                            <tfoot>

                                <tr class="font-semibold text-slate-900">

                                    <td class="pt-3">รวมทั้งหมด</td>

                                    @foreach ($agingTotals as $t)

                                        <td class="pt-3 text-right">{{ number_format($t) }}</td>

                                    @endforeach

                                    <td class="pt-3 text-right">{{ number_format(array_sum($agingTotals)) }}</td>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                    <p class="mt-3 text-xs text-slate-400">ข้อมูลตัวอย่าง หน่วยเป็นบาท</p>

                </div>

            </div>

        </section>

        {{-- ===== รายงาน ===== --}}

        <section id="reports" class="mx-auto max-w-6xl scroll-mt-6 px-5 pb-16 sm:px-8">

            <div class="grid gap-8 lg:grid-cols-[1fr_1.4fr] lg:gap-14">

                <div>

                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">ดูกำไรขั้นต้นได้ทุกวัน</h2>

                    <p class="mt-2 text-slate-600">ยอดขายมาจากบิลจริง ต้นทุนมาจากใบสั่งซื้อที่รับของเข้า รายงานจึงคำนวณให้เองโดยไม่ต้องทำตารางเพิ่ม</p>

                    <ul class="mt-4 space-y-2 text-sm text-slate-600">

                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>รายงานภาพรวมยอดขายและกำไรขั้นต้น</li>

                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>รายงานลูกหนี้และเจ้าหนี้ตามอายุหนี้</li>

                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>ความเคลื่อนไหวสต็อกรายสินค้า</li>

                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>ส่งออกเป็น PDF ได้ทั้งรายงานภาพรวม ลูกหนี้ และเจ้าหนี้</li>

                    </ul>

                    <dl class="mt-6 grid grid-cols-2 gap-4">

                        <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">

                            <dt class="text-xs text-slate-500">ยอดขายสัปดาห์นี้</dt>

                            <dd class="mt-1 text-xl font-semibold text-slate-900">฿{{ number_format($weekSales) }}</dd>

                        </div>

                        <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">

                            <dt class="text-xs text-slate-500">กำไรขั้นต้น</dt>

                            <dd class="mt-1 text-xl font-semibold text-slate-900">฿{{ number_format($weekProfit) }}</dd>

                        </div>

                    </dl>

                    <p class="mt-3 text-xs text-slate-400">ข้อมูลตัวอย่างเพื่อแสดงหน้าตาของรายงาน</p>

                </div>

                <div class="self-start rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200" role="img" aria-label="กราฟตัวอย่างยอดขายและกำไรขั้นต้นรายวัน">

                    <div class="flex items-center justify-between text-xs text-slate-500">

                        <span class="font-semibold text-slate-800">ยอดขายรายวัน (บาท)</span>

                        <span class="flex items-center gap-3">

                            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-ink-900"></span>ยอดขาย</span>

                            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-amber-500"></span>กำไรขั้นต้น</span>

                        </span>

                    </div>

                    <div class="mt-6 flex h-48 items-end justify-between gap-2 sm:gap-4">

                        @foreach ($mockWeek as [$day, $sales, $profit])

                            <div class="flex h-full flex-1 flex-col justify-end">

                                <div class="mb-1 text-center text-[10px] text-slate-500">{{ number_format($sales / 1000, 1) }}&#x6B;</div>

                                <div class="relative w-full overflow-hidden rounded-t-md bg-ink-900" style="height: {{ round($sales / $weekMax * 100) }}%">

                                    <div class="absolute bottom-0 left-0 w-full bg-amber-500" style="height: {{ round($profit / $sales * 100) }}%"></div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                    <div class="mt-2 flex justify-between gap-2 text-center text-xs text-slate-500 sm:gap-4">

                        @foreach ($mockWeek as [$day])

                            <span class="flex-1">{{ $day }}</span>

                        @endforeach

                    </div>

                </div>

            </div>

        </section>

        {{-- ===== สิทธิ์ตามตำแหน่งและความปลอดภัย ===== --}}

        <section id="security" class="mx-auto max-w-6xl scroll-mt-6 px-5 pb-16 sm:px-8">

            <div class="grid gap-8 lg:grid-cols-[1fr_1.4fr] lg:gap-14">

                <div>

                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">แต่ละคนเห็นเฉพาะงานของตัวเอง</h2>

                    <p class="mt-2 text-slate-600">ผู้จัดการกำหนดตำแหน่งให้พนักงานแต่ละคน เมนูที่ไม่เกี่ยวกับงานจะไม่แสดง และเข้าตรงๆ ไม่ได้</p>

                    <ul class="mt-6 space-y-4">

                        @foreach ($security as [$title, $desc])

                            <li class="flex gap-3">

                                <span class="mt-1 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs text-amber-400" aria-hidden="true">✓</span>

                                <div>

                                    <div class="text-sm font-semibold text-slate-900">{{ $title }}</div>

                                    <div class="text-sm text-slate-600">{{ $desc }}</div>

                                </div>

                            </li>

                        @endforeach

                    </ul>

                </div>

                <div class="space-y-6 self-start">

                    <dl class="divide-y divide-slate-200 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

                        @foreach ($roles as [$role, $can])

                            <div class="grid gap-1 px-5 py-4 sm:grid-cols-[9rem_1fr] sm:gap-4">

                                <dt class="font-semibold text-slate-900">{{ $role }}</dt>

                                <dd class="text-sm leading-relaxed text-slate-600">{{ $can }}</dd>

                            </div>

                        @endforeach

                    </dl>

                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                        <h3 class="text-sm font-semibold text-slate-900">สิทธิ์ย่อย {{ $permissionCount }} รายการ ที่ผู้จัดการกำหนดได้</h3>

                        <div class="mt-4 space-y-4">

                            @foreach ($permissions as $group => $list)

                                <div>

                                    <div class="text-xs font-medium text-amber-500">{{ $group }}</div>

                                    <div class="mt-1.5 flex flex-wrap gap-1.5">

                                        @foreach ($list as $perm)

                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700">{{ $perm }}</span>

                                        @endforeach

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            </div>

        </section>

        {{-- ===== เทคโนโลยีที่ใช้ ===== --}}

        <section id="tech" class="mx-auto max-w-6xl scroll-mt-6 px-5 pb-16 sm:px-8">

            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">สร้างด้วยเทคโนโลยีอะไร</h2>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">

                @foreach ($tech as [$name, $role])

                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">

                        <div class="text-sm font-semibold text-slate-900">{{ $name }}</div>

                        <div class="mt-1 text-xs text-slate-500">{{ $role }}</div>

                    </div>

                @endforeach

            </div>

        </section>

        {{-- ===== คำถามที่พบบ่อย ===== --}}

        <section id="faq" class="mx-auto max-w-3xl scroll-mt-6 px-5 pb-16 sm:px-8">

            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">คำถามที่พบบ่อย</h2>

            <div class="mt-8 divide-y divide-slate-200 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

                @foreach ($faqs as [$q, $a])

                    <details class="group px-5 py-4">

                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">

                            {{ $q }}

                            <span class="faq-icon flex-shrink-0 text-xl leading-none text-amber-500 transition-transform" aria-hidden="true">+</span>

                        </summary>

                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</p>

                    </details>

                @endforeach

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

        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">

            <span>&copy; {{ date('Y') }} {{ $companyName }}</span>

            <span>ระบบบริหารร้านอะไหล่ POS และ ERP</span>

        </div>

    </footer>

</body>

</html>
