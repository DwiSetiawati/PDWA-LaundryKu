// ============================================================
//  public/js/orders.js — Script Halaman Input Pesanan
// ============================================================

// ── Validasi Nama ─────────────────────────────────────────────
function formatNama(el) {
    let val = el.value.replace(/[0-9]/g, '');
    val = val.replace(/\b\w/g, c => c.toUpperCase());
    if (el.value !== val) {
        const selStart = el.selectionStart;
        if (typeof selStart === 'number' && !isNaN(selStart)) {
            const pos = selStart - (el.value.length - val.length);
            el.value = val;
            el.setSelectionRange(Math.max(0, pos), Math.max(0, pos));
        } else {
            el.value = val;
        }
    }
}

function blockAngkaNama(e) {
    const key = e.key;
    if (/^[0-9]$/.test(key) || (e.keyCode >= 96 && e.keyCode <= 105)) {
        e.preventDefault();
        const errEl = document.getElementById('namaError');
        if (errEl) { errEl.style.display = 'block'; setTimeout(() => errEl.style.display = 'none', 2000); }
    }
}

// ── Validasi Nomor WA ─────────────────────────────────────────
function filterAngkaSaja(el) {
    el.value = el.value.replace(/[^0-9]/g, '');
}

function blockHurufWA(e) {
    const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
    if (allowed.includes(e.key) || /^[0-9]$/.test(e.key)) return;
    e.preventDefault();
}

// ── Preview Harga ─────────────────────────────────────────────
function updatePreview() {
    const serviceSelect = document.getElementById('serviceSelect');
    const weightInput   = document.getElementById('weightInput');
    const pricePreview  = document.getElementById('pricePreview');
    if (!serviceSelect || !weightInput || !pricePreview) return;

    const opt    = serviceSelect.options[serviceSelect.selectedIndex];
    const price  = parseFloat(opt?.dataset.price || 0);
    const days   = parseInt(opt?.dataset.days || 0);
    const weight = parseFloat(weightInput.value || 0);

    if (price > 0 && weight > 0) {
        const total = price * weight;
        const est   = new Date();
        est.setDate(est.getDate() + days);
        const opts  = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('previewTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('previewDate').textContent  = est.toLocaleDateString('id-ID', opts);
        pricePreview.style.display = 'block';
    } else {
        pricePreview.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const serviceSelect = document.getElementById('serviceSelect');
    const weightInput   = document.getElementById('weightInput');
    if (serviceSelect) serviceSelect.addEventListener('change', updatePreview);
    if (weightInput)   weightInput.addEventListener('input', updatePreview);
    updatePreview();
});
