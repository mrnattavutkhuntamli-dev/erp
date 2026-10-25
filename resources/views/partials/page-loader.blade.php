{{-- resources/views/partials/page-loader.blade.php --}}
{{-- ใช้งาน: @include('partials.page-loader') ต่อจากแท็ก <body> ใน layouts/app.blade.php --}}
{{-- ใช้ตัวแปร $companyName / $companyLogo จาก layout (include แล้วเข้าถึงได้อัตโนมัติ) --}}

@php
    $plName = $companyName ?? 'Mini ERP';
    $plLogo = $companyLogo ?? null;
@endphp

<style>
    /* สไตล์ของ loader เขียนเองทั้งหมด ไม่พึ่ง Tailwind เพื่อให้แสดงผลถูกต้องตั้งแต่เฟรมแรก */
    #page-loader {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px;
        text-align: center;
        color: #fff;
        font-family: "IBM Plex Sans Thai", ui-sans-serif, system-ui, sans-serif;
        background:
            radial-gradient(circle at 50% 38%, #24385a 0%, #1a2a47 28%, #122038 58%, #0c1729 100%);
        transition: opacity 300ms ease, visibility 300ms ease;
    }
    #page-loader.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* โลโก้ + วงแหวนหมุน */
    #page-loader .pl-mark {
        position: relative;
        display: grid;
        place-items: center;
        width: 88px;
        height: 88px;
    }
    #page-loader .pl-glow {
        position: absolute;
        inset: -18px;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(242,160,43,.28) 0%, rgba(242,160,43,0) 68%);
        animation: pl-pulse 2s ease-in-out infinite;
    }
    #page-loader .pl-ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        border: 3px solid rgba(255,255,255,.08);
        border-top-color: #f2a02b;
        border-right-color: rgba(242,160,43,.35);
        animation: pl-spin 1s linear infinite;
    }
    #page-loader .pl-logo {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        border-radius: 15px;
        background: #f2a02b;
        color: #0c1729;
        font-size: 26px;
        font-weight: 700;
        box-shadow: 0 8px 24px rgba(0,0,0,.35);
    }
    #page-loader .pl-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 7px;
        border-radius: inherit;
        background: #fff;
    }

    /* ข้อความ */
    #page-loader .pl-name {
        margin-top: 28px;
        max-width: 100%;
        font-size: 19px;
        font-weight: 600;
        letter-spacing: -0.01em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #page-loader .pl-sub {
        margin-top: 6px;
        font-size: 13px;
        color: #94a3b8;
    }

    /* แถบโหลดแบบไหลไปมา */
    #page-loader .pl-bar {
        margin-top: 26px;
        width: 168px;
        height: 3px;
        overflow: hidden;
        border-radius: 9999px;
        background: rgba(255,255,255,.1);
    }
    #page-loader .pl-bar::after {
        content: "";
        display: block;
        width: 38%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #f5b04c, #f2a02b);
        animation: pl-slide 1.3s ease-in-out infinite;
    }

    @keyframes pl-spin  { to { transform: rotate(360deg); } }
    @keyframes pl-slide { 0% { transform: translateX(-110%); } 100% { transform: translateX(360%); } }
    @keyframes pl-pulse { 0%, 100% { opacity: .55; transform: scale(.95); } 50% { opacity: 1; transform: scale(1.05); } }

    @media (prefers-reduced-motion: reduce) {
        #page-loader .pl-ring { animation-duration: 2.5s; }
        #page-loader .pl-bar::after { animation-duration: 2.5s; }
        #page-loader .pl-glow { animation: none; opacity: .8; }
    }

    /* ไม่แสดงตอนพิมพ์ */
    @media print { #page-loader { display: none !important; } }
</style>

<div id="page-loader" role="status" aria-live="polite">
    <div class="pl-mark">
        <span class="pl-glow"></span>
        <span class="pl-ring"></span>
        <div class="pl-logo notranslate" translate="no">
            @if ($plLogo)
                <img src="{{ asset('storage/logo/' . $plLogo) }}" alt="">
            @else
                {{ mb_strtoupper(mb_substr($plName, 0, 1)) }}
            @endif
        </div>
    </div>

    <div class="pl-name notranslate" translate="no">{{ $plName }}</div>
    <div class="pl-sub">กำลังโหลด...</div>
    <div class="pl-bar" aria-hidden="true"></div>
</div>

{{-- กรณีปิด JavaScript ให้ซ่อน loader เพื่อไม่ให้ค้างหน้าจอ --}}
<noscript><style>#page-loader { display: none !important; }</style></noscript>

<script>
    (function () {
        const loader = document.getElementById('page-loader');
        if (!loader) return;

        const FADE_MS = 300;       // ต้องตรงกับ transition ใน CSS
        const SHOW_DELAY_MS = 200; // หน้าโหลดเร็วกว่านี้จะไม่แสดง loader ตอนเปลี่ยนหน้า
        const FAILSAFE_MS = 15000; // กัน loader ค้าง (เช่น ลิงก์ดาวน์โหลดไฟล์)
        let showTimer = null;
        let failTimer = null;

        function hideLoader() {
            clearTimeout(showTimer);
            clearTimeout(failTimer);
            loader.classList.add('is-hidden');
            setTimeout(() => {
                if (loader.classList.contains('is-hidden')) loader.style.display = 'none';
            }, FADE_MS);
        }

        function showLoader(delay = SHOW_DELAY_MS) {
            clearTimeout(showTimer);
            showTimer = setTimeout(() => {
                loader.style.display = '';
                void loader.offsetWidth; // บังคับ reflow ให้ transition ทำงาน
                loader.classList.remove('is-hidden');
                failTimer = setTimeout(hideLoader, FAILSAFE_MS);
            }, delay);
        }

        // 1) ซ่อนเมื่อ DOM พร้อม (ไม่รอรูป/สคริปต์ภายนอก) และมี load เป็นตัวสำรอง
        if (document.readyState !== 'loading') {
            hideLoader();
        } else {
            document.addEventListener('DOMContentLoaded', hideLoader);
            window.addEventListener('load', hideLoader);
        }

        // 2) แสดงตอนกดลิงก์ไปหน้าอื่นในเว็บเดียวกัน
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

            const a = e.target.closest('a[href]');
            if (!a) return;
            if (a.target === '_blank' || a.hasAttribute('download')) return;
            if (a.origin !== location.origin) return;
            if (a.hasAttribute('data-no-loader')) return;

            const href = a.getAttribute('href') || '';
            if (href.charAt(0) === '#') return; // รวมถึง href="#"
            if (a.pathname === location.pathname && a.search === location.search && a.hash) return;
            if (/^(mailto|tel|javascript):/i.test(href)) return;

            showLoader();
        });

        // 3) แสดงตอนส่งฟอร์ม
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (e.defaultPrevented) return;
            if (form.target === '_blank' || form.hasAttribute('data-no-loader')) return;
            showLoader(0);
        });

        // 4) กดปุ่ม Back/Forward แล้วหน้าถูกดึงจาก bfcache ต้องซ่อน loader
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) hideLoader();
        });
    })();
</script>
