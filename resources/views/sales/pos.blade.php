@extends('layouts.app')

@section('title', 'ขายหน้าร้าน (POS)')

@push('scripts')
<script>
    // ============================================================
    //  ข้อมูลจาก Laravel
    // ============================================================
    const products = @json($products);
    const customers = @json($customers);
    const totalProducts = {{ $products->count() }};

    let cart = [];
    let payMethod = 'cash';

    // ============================================================
    //  ตัวช่วยทั่วไป
    // ============================================================
    const $ = id => document.getElementById(id);
    const baht = n => '฿' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function toast(msg, type = 'info') {
        const el = document.createElement('div');
        el.className = 'pos-toast pos-toast-' + type;
        el.textContent = msg;
        $('toast-root').appendChild(el);
        setTimeout(() => el.remove(), 2600);
    }

    // ============================================================
    //  ตะกร้าสินค้า
    // ============================================================
    function addToCart(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;
        const existing = cart.find(i => i.product_id === productId);

        if (existing) {
            if (existing.quantity < product.stock) {
                existing.quantity++;
            } else {
                toast('สินค้าในสต็อกไม่เพียงพอ', 'error');
                return;
            }
        } else {
            if (product.stock < 1) {
                toast('สินค้าหมดสต็อก', 'error');
                return;
            }
            cart.push({
                product_id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                stock: product.stock,
                quantity: 1
            });
        }
        renderCart();
    }

    function updateQuantity(index, change) {
        const item = cart[index];
        const newQty = item.quantity + change;
        if (newQty > item.stock) {
            toast('สินค้าในสต็อกไม่เพียงพอ', 'error');
            return;
        }
        if (newQty > 0) item.quantity = newQty;
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        if (cart.length && confirm('ล้างสินค้าทั้งหมดในตะกร้า?')) {
            cart = [];
            renderCart();
        }
    }

    function getTotals() {
        const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        let discount = parseFloat($('discount').value) || 0;
        discount = Math.min(Math.max(discount, 0), subtotal);
        return { subtotal, discount, total: subtotal - discount };
    }

    function renderCart() {
        $('cart-body').innerHTML = cart.map((item, index) => `
            <li class="pos-line">
                <div class="pos-line-main">
                    <div class="pos-line-name">${esc(item.name)}</div>
                    <div class="pos-line-unit">${baht(item.price)} / ชิ้น</div>
                </div>
                <div class="pos-step">
                    <button type="button" onclick="updateQuantity(${index}, -1)" aria-label="ลดจำนวน">−</button>
                    <span>${item.quantity}</span>
                    <button type="button" onclick="updateQuantity(${index}, 1)" aria-label="เพิ่มจำนวน">+</button>
                </div>
                <div class="pos-line-total">${baht(item.price * item.quantity)}</div>
                <button type="button" class="pos-x" onclick="removeFromCart(${index})" aria-label="ลบ ${esc(item.name)}">×</button>
            </li>
        `).join('');

        $('cart-empty').hidden = cart.length > 0;
        $('clear-cart').hidden = cart.length === 0;
        $('cart-count').textContent = cart.reduce((s, i) => s + i.quantity, 0);

        // ป้ายจำนวนบนการ์ดสินค้า
        document.querySelectorAll('[data-badge]').forEach(b => {
            const it = cart.find(i => String(i.product_id) === b.dataset.badge);
            b.hidden = !it;
            if (it) b.textContent = it.quantity;
        });

        refreshTotals();
    }

    function refreshTotals() {
        const t = getTotals();
        $('subtotal').textContent = baht(t.subtotal);
        $('total').textContent = baht(t.total);
        calculateChange(t.total);
        updatePayButton(t);
    }

    function calculateChange(total) {
        const received = parseFloat($('received_amount').value) || 0;
        const change = received - total;
        const row = $('change-row');
        const el = $('change_amount');

        if (cart.length === 0) {
            el.textContent = '฿0.00';
            row.className = 'pos-change';
        } else if (change >= 0) {
            el.textContent = baht(change);
            row.className = 'pos-change is-ok';
        } else {
            el.textContent = 'ขาดอีก ' + baht(-change);
            row.className = 'pos-change is-short';
        }
    }

    function updatePayButton(t) {
        const btn = $('pay-btn');
        const received = parseFloat($('received_amount').value) || 0;
        const ok = cart.length > 0 && (payMethod !== 'cash' || received >= t.total);
        btn.disabled = !ok;
        btn.textContent = cart.length ? 'ชำระเงิน ' + baht(t.total) : 'ชำระเงิน';
    }

    function setPayment(method) {
        payMethod = method;
        $('payment_method').value = method;
        document.querySelectorAll('[data-method]').forEach(b => {
            const on = b.dataset.method === method;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on);
        });
        $('cash-block').hidden = method !== 'cash';
        refreshTotals();
    }

    function quickCash(amount) {
        $('received_amount').value = amount === null ? getTotals().total.toFixed(2) : amount;
        refreshTotals();
    }

    // ============================================================
    //  ค้นหาสินค้า
    // ============================================================
    function filterProducts() {
        const q = $('product-search').value.trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('.pos-card').forEach(c => {
            const match = !q || c.dataset.search.includes(q);
            c.hidden = !match;
            if (match) shown++;
        });
        $('result-count').textContent = q ? `พบ ${shown} จาก ${totalProducts} รายการ` : `ทั้งหมด ${totalProducts} รายการ`;
        $('no-result').hidden = shown > 0 || totalProducts === 0;
    }

    // ============================================================
    //  เครื่องคิดเลข
    // ============================================================
    const calc = { cur: '0', prev: null, op: null, fresh: false, done: false, expr: '' };
    const OPS = { '+': '+', '-': '−', '*': '×', '/': '÷' };

    function calcFmt(s) {
        s = String(s);
        if (s === 'Error') return s;
        const neg = s.startsWith('-');
        if (neg) s = s.slice(1);
        const [i, d] = s.split('.');
        return (neg ? '-' : '') + i.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (d !== undefined ? '.' + d : '');
    }
    const calcClean = n => String(parseFloat(n.toFixed(10)));

    function calcRender() {
        $('calc-value').textContent = calcFmt(calc.cur);
        $('calc-expr').textContent = calc.expr;
    }

    function calcReset() {
        Object.assign(calc, { cur: '0', prev: null, op: null, fresh: false, done: false, expr: '' });
    }

    function calcError() {
        calcReset();
        calc.cur = 'Error';
        calcRender();
        toast('หารด้วยศูนย์ไม่ได้', 'error');
    }

    function calcCompute(a, op, b) {
        switch (op) {
            case '+': return a + b;
            case '-': return a - b;
            case '*': return a * b;
            case '/': return b === 0 ? NaN : a / b;
        }
    }

    function calcStartNew() {
        calc.cur = '0';
        calc.fresh = false;
        if (calc.done) { calc.done = false; calc.expr = ''; }
    }

    function calcKey(k) {
        if (calc.cur === 'Error') calcReset();

        if (/^\d$/.test(k)) {
            if (calc.fresh || calc.done) calcStartNew();
            if (calc.cur.replace(/[-.]/g, '').length >= 12) return;
            calc.cur = calc.cur === '0' ? k : calc.cur + k;

        } else if (k === '.') {
            if (calc.fresh || calc.done) calcStartNew();
            if (!calc.cur.includes('.')) calc.cur += '.';

        } else if (k in OPS) {
            if (calc.op && !calc.fresh) {
                const r = calcCompute(calc.prev, calc.op, parseFloat(calc.cur));
                if (isNaN(r)) return calcError();
                calc.cur = calcClean(r);
            }
            calc.prev = parseFloat(calc.cur);
            calc.op = k;
            calc.fresh = true;
            calc.done = false;
            calc.expr = calcFmt(calc.prev) + ' ' + OPS[k];

        } else if (k === '=') {
            if (!calc.op) return;
            const b = parseFloat(calc.cur);
            const r = calcCompute(calc.prev, calc.op, b);
            if (isNaN(r)) return calcError();
            calc.expr = `${calcFmt(calc.prev)} ${OPS[calc.op]} ${calcFmt(b)} =`;
            calc.cur = calcClean(r);
            calc.op = null;
            calc.prev = null;
            calc.done = true;
            calc.fresh = false;

        } else if (k === '%') {
            let v = parseFloat(calc.cur);
            v = (calc.op === '+' || calc.op === '-') ? calc.prev * v / 100 : v / 100;
            calc.cur = calcClean(v);

        } else if (k === 'C') {
            calcReset();

        } else if (k === 'B') {
            if (calc.fresh || calc.done) return;
            calc.cur = calc.cur.length > 1 ? calc.cur.slice(0, -1) : '0';
            if (calc.cur === '-') calc.cur = '0';
        }
        calcRender();
    }

    function toggleCalc(force) {
        const el = $('calc');
        const open = force === undefined ? el.hidden : force;
        el.hidden = !open;
        $('calc-toggle').setAttribute('aria-expanded', open);
    }

    // นำผลลัพธ์ไปใช้กับช่องเงินที่รับ / ส่วนลด
    function calcUse(target) {
        if (calc.op && !calc.fresh) calcKey('=');
        if (calc.cur === 'Error') return;
        const v = parseFloat(calc.cur);
        if (!(v >= 0)) {
            toast('ค่าต้องไม่ติดลบ', 'error');
            return;
        }
        if (target === 'received') {
            setPayment('cash');
            $('received_amount').value = v;
            toast('ใส่เป็นเงินที่รับแล้ว ' + baht(v), 'success');
        } else {
            $('discount').value = v;
            toast('ใส่เป็นส่วนลดแล้ว ' + baht(v), 'success');
        }
        refreshTotals();
    }

    // ============================================================
    //  เริ่มต้นระบบ
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        // ค้นหา
        $('product-search').addEventListener('input', filterProducts);
        $('product-search').addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const q = e.target.value.trim().toLowerCase();
            const cards = [...document.querySelectorAll('.pos-card:not([hidden]):not(:disabled)')];
            if (!cards.length) return;
            (cards.find(c => c.dataset.sku === q) || cards[0]).click();
            e.target.value = '';
            filterProducts();
        });

        // เครื่องคิดเลข
        $('calc-keys').addEventListener('click', e => {
            const b = e.target.closest('[data-k]');
            if (b) calcKey(b.dataset.k);
        });

        // คีย์ลัด
        document.addEventListener('keydown', e => {
            if (e.key === 'F2') { e.preventDefault(); $('product-search').focus(); $('product-search').select(); return; }
            if (e.key === 'F4') { e.preventDefault(); toggleCalc(); return; }
            if (e.key === 'Escape' && !$('calc').hidden) { toggleCalc(false); return; }
            if ($('calc').hidden || e.ctrlKey || e.metaKey || e.altKey) return;
            if (['input', 'select', 'textarea'].includes((e.target.tagName || '').toLowerCase())) return;

            const map = { Enter: '=', '=': '=', Backspace: 'B', Delete: 'C', c: 'C', C: 'C', ',': '.' };
            const k = map[e.key] ?? e.key;
            if (/^\d$/.test(k) || ['.', '+', '-', '*', '/', '%', '=', 'B', 'C'].includes(k)) {
                e.preventDefault();
                calcKey(k);
            }
        });

        // ส่งข้อมูลไปบันทึก
        $('pos-form').addEventListener('submit', e => {
            e.preventDefault();

            if (cart.length === 0) {
                toast('กรุณาเลือกสินค้าก่อนทำรายการ', 'error');
                return;
            }

            const t = getTotals();
            const received = payMethod === 'cash' ? (parseFloat($('received_amount').value) || 0) : t.total;
            if (received < t.total) {
                toast('รับเงินมาไม่เพียงพอ', 'error');
                return;
            }

            const btn = $('pay-btn');
            btn.disabled = true;
            btn.textContent = 'กำลังบันทึก...';

            const formData = {
                _token: '{{ csrf_token() }}',
                customer_id: $('customer_id').value,
                items: cart,
                discount: t.discount,
                payment_method: payMethod,
                received_amount: received,
            };

            fetch('{{ route('sales.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    toast('ข้อผิดพลาด: ' + data.message, 'error');
                    refreshTotals();
                }
            })
            .catch(err => {
                toast('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่', 'error');
                console.error(err);
                refreshTotals();
            });
        });

        renderCart();
        setPayment('cash');
        calcRender();
        filterProducts();
    });
</script>
@endpush

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    .pos-root {
        --ink: #16202A;
        --ink-2: #1F2C38;
        --ink-3: #33434F;
        --line: #DCE1E6;
        --canvas: #F4F6F8;
        --mute: #6A7784;
        --amber: #F2A900;
        --amber-soft: #FFF4D6;
        --danger: #D64545;
        --ok: #2E9E6B;
        font-family: 'IBM Plex Sans Thai', 'Noto Sans Thai', system-ui, sans-serif;
        font-variant-numeric: tabular-nums;
        color: var(--ink);
        line-height: 1.5;
    }
    .pos-root *, .pos-root *::before, .pos-root *::after { box-sizing: border-box; }
    .pos-root button, .pos-root input, .pos-root select { font-family: inherit; }
    .pos-root button { cursor: pointer; }
    .pos-root [hidden] { display: none !important; }
    .pos-root :focus-visible { outline: 2px solid var(--amber); outline-offset: 2px; }

    /* ---------- เลย์เอาต์หลัก ---------- */
    .pos {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 400px;
        gap: 20px;
        height: calc(100vh - 120px);
        min-height: 640px;
    }

    /* ---------- ฝั่งสินค้า ---------- */
    .pos-catalog {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }
    .pos-catalog-head {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        padding: 16px 20px;
        border-bottom: 1px solid var(--line);
    }
    .pos-title { font-size: 1.25rem; font-weight: 700; margin: 0; line-height: 1.2; }
    .pos-sub { font-size: .8125rem; color: var(--mute); margin: 2px 0 0; }
    .pos-search {
        flex: 1;
        min-width: 220px;
        height: 44px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        background: var(--canvas);
        border: 1px solid var(--line);
        border-radius: 12px;
        color: var(--mute);
    }
    .pos-search:focus-within { background: #fff; border-color: var(--ink); color: var(--ink); }
    .pos-search input {
        flex: 1;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        font-size: .9375rem;
        color: var(--ink);
    }
    .pos-kbd {
        font-size: .6875rem;
        line-height: 1.4;
        padding: 0 6px;
        border: 1px solid var(--line);
        border-radius: 5px;
        background: #fff;
        color: var(--mute);
    }
    .pos-btn-calc {
        height: 44px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 14px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        font-size: .875rem;
        font-weight: 600;
        color: var(--ink);
        transition: background .15s, color .15s, border-color .15s;
    }
    .pos-btn-calc:hover, .pos-btn-calc[aria-expanded="true"] {
        background: var(--ink);
        border-color: var(--ink);
        color: #fff;
    }

    .pos-grid-wrap { flex: 1; overflow-y: auto; padding: 14px; background: var(--canvas); }
    .pos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 12px;
        align-content: start;
    }
    .pos-card {
        position: relative;
        display: flex;
        flex-direction: column;
        padding: 0;
        text-align: left;
        color: inherit;
        background: #fff;
        border: 0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(22, 32, 42, .08), 0 0 0 1px rgba(22, 32, 42, .04);
        transition: box-shadow .15s, transform .1s;
    }
    .pos-card:hover:not(:disabled) { box-shadow: 0 0 0 2px var(--ink); }
    .pos-card:active:not(:disabled) { transform: scale(.985); }
    .pos-card:disabled { opacity: .55; cursor: not-allowed; }
    .pos-thumb {
        position: relative;
        aspect-ratio: 3 / 2;
        display: flex;
        align-items: center;
        justify-content: center;
        background: hsl(var(--h, 210) 28% 92%);
        color: hsl(var(--h, 210) 30% 42%);
    }
    .pos-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
    .pos-initial { font-size: 2.5rem; font-weight: 700; letter-spacing: .02em; }
    .pos-add {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(22, 32, 42, .72);
        color: #fff;
        transition: background .15s, color .15s;
    }
    .pos-card:hover:not(:disabled) .pos-add { background: var(--amber); color: var(--ink); }
    .pos-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        min-width: 30px;
        height: 30px;
        padding: 0 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: var(--amber);
        color: var(--ink);
        font-size: .9375rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(22, 32, 42, .25);
    }
    .pos-body { flex: 1; display: flex; flex-direction: column; padding: 10px 14px 12px; }
    .pos-name {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.7em;
        font-size: .9375rem;
        font-weight: 700;
        line-height: 1.35;
    }
    .pos-sku { margin-top: 2px; font-size: .75rem; color: var(--mute); }
    .pos-foot { display: flex; align-items: baseline; justify-content: space-between; margin-top: auto; padding-top: 8px; }
    .pos-price { font-size: 1.1875rem; font-weight: 700; }
    .pos-stock { font-size: .75rem; color: var(--mute); }
    .pos-stock.low { color: var(--danger); font-weight: 600; }

    .pos-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 64px 16px;
        text-align: center;
        color: var(--mute);
        font-size: .9375rem;
    }

    /* ---------- ฝั่งตะกร้า ---------- */
    .pos-cart {
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow-y: auto;
        background: var(--ink);
        color: #E8EDF1;
        border-radius: 16px;
    }
    .pos-cart-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--ink-3);
    }
    .pos-cart-title { display: flex; align-items: center; gap: 10px; margin: 0; font-size: 1.125rem; font-weight: 700; color: #fff; }
    .pos-count {
        min-width: 26px;
        height: 26px;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background: var(--ink-3);
        font-size: .8125rem;
        font-weight: 600;
    }
    .pos-link { border: 0; background: none; padding: 4px; font-size: .8125rem; color: #9FB0BF; }
    .pos-link:hover { color: #fff; text-decoration: underline; }

    .pos-lines-wrap { flex: 1; min-height: 140px; overflow-y: auto; padding: 4px 20px; }
    .pos-lines { list-style: none; margin: 0; padding: 0; }
    .pos-line {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto 84px 24px;
        align-items: center;
        gap: 10px;
        padding: 12px 0;
        border-bottom: 1px solid var(--ink-2);
    }
    .pos-line-name { font-size: .875rem; font-weight: 600; color: #fff; line-height: 1.35; }
    .pos-line-unit { font-size: .75rem; color: #8798A7; }
    .pos-step { display: inline-flex; align-items: center; background: var(--ink-2); border-radius: 8px; }
    .pos-step button {
        width: 28px;
        height: 28px;
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
    }
    .pos-step button:hover { background: var(--ink-3); }
    .pos-step span { min-width: 24px; text-align: center; font-weight: 700; font-size: .875rem; color: #fff; }
    .pos-line-total { text-align: right; font-size: .875rem; font-weight: 700; color: #fff; }
    .pos-x { width: 24px; height: 24px; border: 0; background: none; color: #8798A7; font-size: 1.25rem; line-height: 1; border-radius: 6px; }
    .pos-x:hover { color: #fff; background: var(--danger); }
    .pos-cart-empty { padding: 36px 8px; text-align: center; color: #8798A7; font-size: .875rem; }

    .pos-checkout { padding: 16px 20px 20px; border-top: 1px solid var(--ink-3); background: var(--ink-2); border-radius: 0 0 16px 16px; }
    .pos-field { display: block; margin-bottom: 12px; }
    .pos-label { display: block; margin-bottom: 4px; font-size: .75rem; color: #9FB0BF; }
    .pos-input {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border: 1px solid var(--ink-3);
        border-radius: 10px;
        background: var(--canvas);
        color: #030303;
        font-size: .875rem;
    }
    .pos-input:focus { outline: 2px solid var(--amber); outline-offset: 0; border-color: var(--amber); }
    .pos-input.num { text-align: right; font-weight: 600; }
    .pos-input option { color: #000; }

    .pos-seg { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 12px; }
    .pos-seg button {
        height: 38px;
        border: 1px solid var(--ink-3);
        border-radius: 10px;
        background: var(--ink);
        color: #C9D4DD;
        font-size: .8125rem;
        font-weight: 600;
    }
    .pos-seg button:hover { border-color: #5B6E7E; }
    .pos-seg button.is-active { background: #fff; color: var(--ink); border-color: #fff; }

    .pos-sum { display: flex; justify-content: space-between; align-items: baseline; font-size: .875rem; color: #9FB0BF; }
    .pos-sum strong { color: #fff; font-weight: 600; }
    .pos-total { display: flex; justify-content: space-between; align-items: baseline; margin: 8px 0 14px; padding-top: 10px; border-top: 1px dashed var(--ink-3); }
    .pos-total span:first-child { font-size: 1rem; font-weight: 600; color: #fff; }
    .pos-total span:last-child { font-size: 2rem; font-weight: 700; color: #fff; line-height: 1.1; }

    .pos-quick { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 12px; }
    .pos-quick button {
        height: 30px;
        padding: 0 11px;
        border: 1px solid var(--ink-3);
        border-radius: 999px;
        background: transparent;
        color: #C9D4DD;
        font-size: .8125rem;
        font-weight: 500;
    }
    .pos-quick button:hover { background: var(--ink-3); color: #fff; }

    .pos-change {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding: 10px 12px;
        margin-bottom: 14px;
        border-radius: 10px;
        background: var(--ink);
        color: #9FB0BF;
        font-size: .875rem;
    }
    .pos-change strong { font-size: 1.25rem; font-weight: 700; color: #fff; }
    .pos-change.is-ok strong { color: #5FD3A0; }
    .pos-change.is-short strong { color: #FF8A80; font-size: 1rem; }

    .pos-pay {
        width: 100%;
        height: 52px;
        border: 0;
        border-radius: 12px;
        background: var(--amber);
        color: var(--ink);
        font-size: 1.0625rem;
        font-weight: 700;
        transition: filter .15s, transform .1s;
    }
    .pos-pay:hover:not(:disabled) { filter: brightness(1.06); }
    .pos-pay:active:not(:disabled) { transform: scale(.99); }
    .pos-pay:disabled { background: var(--ink-3); color: #7E90A0; cursor: not-allowed; }

    /* ---------- เครื่องคิดเลข ---------- */
    .pos-calc {
        position: fixed;
        left: 20px;
        bottom: 20px;
        z-index: 60;
        width: 304px;
        padding: 14px;
        border-radius: 18px;
        background: var(--ink);
        color: #fff;
        box-shadow: 0 18px 48px rgba(10, 18, 26, .38);
    }
    .pos-calc-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; font-size: .8125rem; color: #9FB0BF; }
    .pos-calc-head button { border: 0; background: none; color: #9FB0BF; font-size: 1.25rem; line-height: 1; border-radius: 6px; width: 26px; height: 26px; }
    .pos-calc-head button:hover { color: #fff; background: var(--ink-3); }
    .pos-calc-screen { min-height: 78px; padding: 10px 12px; margin-bottom: 10px; border-radius: 12px; background: var(--ink-2); text-align: right; }
    .pos-calc-expr { min-height: 1.25rem; font-size: .8125rem; color: #8798A7; }
    .pos-calc-value { font-size: 2.125rem; font-weight: 600; line-height: 1.2; word-break: break-all; }
    .pos-calc-keys { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .pos-calc-keys button {
        height: 48px;
        border: 0;
        border-radius: 12px;
        background: var(--ink-2);
        color: #fff;
        font-size: 1.125rem;
        font-weight: 500;
        transition: background .1s, transform .08s;
    }
    .pos-calc-keys button:hover { background: var(--ink-3); }
    .pos-calc-keys button:active { transform: scale(.95); }
    .pos-calc-keys .fn { color: #9FB0BF; font-size: 1rem; }
    .pos-calc-keys .op { background: #3A3320; color: var(--amber); font-size: 1.25rem; }
    .pos-calc-keys .op:hover { background: #4A4026; }
    .pos-calc-keys .eq { background: var(--amber); color: var(--ink); font-weight: 700; }
    .pos-calc-keys .eq:hover { background: #FFB920; }
    .pos-calc-keys .wide { grid-column: span 2; }
    .pos-calc-use { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
    .pos-calc-use button {
        height: 36px;
        border: 1px solid var(--ink-3);
        border-radius: 10px;
        background: transparent;
        color: #C9D4DD;
        font-size: .8125rem;
        font-weight: 500;
    }
    .pos-calc-use button:hover { background: var(--ink-3); color: #fff; }

    /* ---------- แจ้งเตือน ---------- */
    #toast-root { position: fixed; top: 76px; right: 20px; z-index: 80; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
    .pos-toast {
        max-width: 320px;
        padding: 10px 14px;
        border-radius: 10px;
        border-left: 4px solid var(--mute);
        background: var(--ink);
        color: #fff;
        font-size: .875rem;
        box-shadow: 0 8px 24px rgba(10, 18, 26, .25);
        animation: pos-toast-in .18s ease-out;
    }
    .pos-toast-error { border-left-color: var(--danger); }
    .pos-toast-success { border-left-color: var(--ok); }
    @keyframes pos-toast-in { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

    /* ---------- จอเล็ก ---------- */
    @media (max-width: 1023px) {
        .pos { grid-template-columns: 1fr; height: auto; min-height: 0; }
        .pos-grid-wrap { max-height: 70vh; }
        .pos-cart { min-height: 560px; overflow: visible; }
        .pos-calc { left: 0; right: 0; bottom: 0; width: auto; border-radius: 18px 18px 0 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pos-root *, .pos-root *::before { transition: none !important; animation: none !important; }
    }
</style>

<div class="pos-root">
    <div class="pos">

        <!-- ================= ฝั่งซ้าย: สินค้า ================= -->
        <section class="pos-catalog" aria-label="รายการสินค้า">
            <header class="pos-catalog-head">
                <div>
                    <h1 class="pos-title">ขายหน้าร้าน</h1>
                    <p class="pos-sub" id="result-count">ทั้งหมด {{ $products->count() }} รายการ</p>
                </div>

                <label class="pos-search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" id="product-search" placeholder="ค้นหาชื่อสินค้าหรือ SKU แล้วกด Enter เพื่อเพิ่ม" autocomplete="off">
                    <span class="pos-kbd">F2</span>
                </label>

                <button type="button" id="calc-toggle" class="pos-btn-calc" onclick="toggleCalc()" aria-expanded="false" aria-controls="calc">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/></svg>
                    เครื่องคิดเลข
                    <span class="pos-kbd">F4</span>
                </button>
            </header>

            <div class="pos-grid-wrap">
                @if($products->count() > 0)
                    <div class="pos-grid">
                        @foreach($products as $product)
                            <button type="button"
                                    class="pos-card"
                                    onclick="addToCart({{ $product->id }})"
                                    data-search="{{ mb_strtolower($product->name . ' ' . $product->sku) }}"
                                    data-sku="{{ mb_strtolower($product->sku) }}"
                                    {{ $product->stock <= 0 ? 'disabled' : '' }}>
                                <span class="pos-thumb" style="--h: {{ crc32($product->name) % 360 }}">
                                    @if($product->primaryImage)
                                        <img src="{{ asset('storage/' . $product->primaryImage->image_path) }}" alt="{{ $product->name }}" loading="lazy">
                                    @else
                                        <span class="pos-initial" aria-hidden="true">{{ mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $product->name), 0, 2) }}</span>
                                    @endif
                                    <span class="pos-add" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                                    </span>
                                    <span class="pos-badge" data-badge="{{ $product->id }}" hidden></span>
                                </span>
                                <span class="pos-body">
                                    <span class="pos-name">{{ $product->name }}</span>
                                    <span class="pos-sku">{{ $product->sku }}</span>
                                    <span class="pos-foot">
                                        <span class="pos-price">฿{{ number_format($product->price, 0) }}</span>
                                        <span class="pos-stock {{ $product->stock < 10 ? 'low' : '' }}">
                                            {{ $product->stock <= 0 ? 'หมด' : 'เหลือ ' . $product->stock }}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="pos-empty" id="no-result" hidden>
                        <strong>ไม่พบสินค้าที่ตรงกับคำค้นหา</strong>
                        <span>ลองค้นหาด้วยชื่ออื่น หรือรหัส SKU</span>
                    </div>
                @else
                    <div class="pos-empty" id="no-result">
                        <svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <strong>ยังไม่มีสินค้าที่มีสต็อกในระบบ</strong>
                        <span>เพิ่มสินค้าและสต็อกก่อน จึงจะขายหน้าร้านได้</span>
                    </div>
                @endif
            </div>
        </section>

        <!-- ================= ฝั่งขวา: ตะกร้า + ชำระเงิน ================= -->
        <aside class="pos-cart" aria-label="ตะกร้าสินค้า">
            <div class="pos-cart-head">
                <h2 class="pos-cart-title">ตะกร้า <span class="pos-count" id="cart-count">0</span></h2>
                <button type="button" class="pos-link" id="clear-cart" onclick="clearCart()" hidden>ล้างตะกร้า</button>
            </div>

            <div class="pos-lines-wrap">
                <ul class="pos-lines" id="cart-body"></ul>
                <div class="pos-cart-empty" id="cart-empty">
                    ยังไม่มีสินค้าในตะกร้า<br>แตะที่สินค้าทางซ้าย หรือค้นหาแล้วกด Enter
                </div>
            </div>

            <form id="pos-form" class="pos-checkout">
                <label class="pos-field">
                    <span class="pos-label">ลูกค้า (ไม่บังคับ)</span>
                    <select name="customer_id" id="customer_id" class="pos-input">
                        <option value="">ลูกค้าทั่วไป</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} {{ $customer->license_plate ? '(' . $customer->license_plate . ')' : '' }}</option>
                        @endforeach
                    </select>
                </label>

                <span class="pos-label">วิธีชำระเงิน</span>
                <input type="hidden" name="payment_method" id="payment_method" value="cash">
                <div class="pos-seg" role="group" aria-label="วิธีชำระเงิน">
                    <button type="button" data-method="cash" onclick="setPayment('cash')">เงินสด</button>
                    <button type="button" data-method="transfer" onclick="setPayment('transfer')">โอนเงิน</button>
                    <button type="button" data-method="credit_card" onclick="setPayment('credit_card')">บัตรเครดิต</button>
                </div>

                <label class="pos-field">
                    <span class="pos-label">ส่วนลด (บาท)</span>
                    <input type="number" name="discount" id="discount" value="0" min="0" step="any" oninput="refreshTotals()" class="pos-input num">
                </label>

                <div class="pos-sum"><span>ยอดรวม</span><strong id="subtotal">฿0.00</strong></div>
                <div class="pos-total"><span>ยอดสุทธิ</span><span id="total">฿0.00</span></div>

                <div id="cash-block">
                    <label class="pos-field" style="margin-bottom:0">
                        <span class="pos-label">รับเงินมา (บาท)</span>
                        <input type="number" name="received_amount" id="received_amount" value="0" min="0" step="any" oninput="refreshTotals()" class="pos-input num" style="height:46px;font-size:1.25rem">
                    </label>
                    <div class="pos-quick" aria-label="จำนวนเงินที่รับบ่อย">
                        <button type="button" onclick="quickCash(null)">พอดี</button>
                        <button type="button" onclick="quickCash(20)">20</button>
                        <button type="button" onclick="quickCash(50)">50</button>
                        <button type="button" onclick="quickCash(100)">100</button>
                        <button type="button" onclick="quickCash(500)">500</button>
                        <button type="button" onclick="quickCash(1000)">1,000</button>
                    </div>
                    <div class="pos-change" id="change-row">
                        <span>เงินทอน</span>
                        <strong id="change_amount">฿0.00</strong>
                    </div>
                </div>

                <button type="submit" class="pos-pay" id="pay-btn" disabled>ชำระเงิน</button>
            </form>
        </aside>
    </div>

    <!-- ================= เครื่องคิดเลข (ลอย) ================= -->
    <div class="pos-calc" id="calc" role="dialog" aria-label="เครื่องคิดเลข" hidden>
        <div class="pos-calc-head">
            <span>เครื่องคิดเลข · พิมพ์ตัวเลขจากคีย์บอร์ดได้</span>
            <button type="button" onclick="toggleCalc(false)" aria-label="ปิดเครื่องคิดเลข">×</button>
        </div>
        <div class="pos-calc-screen" aria-live="polite">
            <div class="pos-calc-expr" id="calc-expr"></div>
            <div class="pos-calc-value" id="calc-value">0</div>
        </div>
        <div class="pos-calc-keys" id="calc-keys">
            <button type="button" class="fn" data-k="C">C</button>
            <button type="button" class="fn" data-k="B" aria-label="ลบตัวสุดท้าย">⌫</button>
            <button type="button" class="fn" data-k="%">%</button>
            <button type="button" class="op" data-k="/" aria-label="หาร">÷</button>

            <button type="button" data-k="7">7</button>
            <button type="button" data-k="8">8</button>
            <button type="button" data-k="9">9</button>
            <button type="button" class="op" data-k="*" aria-label="คูณ">×</button>

            <button type="button" data-k="4">4</button>
            <button type="button" data-k="5">5</button>
            <button type="button" data-k="6">6</button>
            <button type="button" class="op" data-k="-" aria-label="ลบ">−</button>

            <button type="button" data-k="1">1</button>
            <button type="button" data-k="2">2</button>
            <button type="button" data-k="3">3</button>
            <button type="button" class="op" data-k="+" aria-label="บวก">+</button>

            <button type="button" class="wide" data-k="0">0</button>
            <button type="button" data-k=".">.</button>
            <button type="button" class="eq" data-k="=">=</button>
        </div>
        <div class="pos-calc-use">
            <button type="button" onclick="calcUse('received')">ใช้เป็นเงินที่รับ</button>
            <button type="button" onclick="calcUse('discount')">ใช้เป็นส่วนลด</button>
        </div>
    </div>

    <div id="toast-root" aria-live="polite"></div>
</div>
@endsection
