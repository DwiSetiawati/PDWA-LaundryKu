// ============================================================
//  public/js/manage_orders.js — Script Kelola Pesanan
// ============================================================

function openEditModal(data) {
    document.getElementById('edit_order_id').value   = data.id;

    const nameInput = document.getElementById('edit_cust_name');
    nameInput.value  = data.cust_name;
    formatNamaEdit(nameInput);

    const phoneInput = document.getElementById('edit_cust_phone');
    phoneInput.value = data.cust_phone;
    filterAngkaSajaEdit(phoneInput);

    document.getElementById('edit_cust_addr').value  = data.cust_addr;
    document.getElementById('edit_weight').value     = data.weight_kg;
    document.getElementById('edit_notes').value      = data.notes;

    const svcSelect = document.getElementById('edit_service_id');
    for (let i = 0; i < svcSelect.options.length; i++) {
        if (svcSelect.options[i].value == data.service_id) {
            svcSelect.selectedIndex = i;
            break;
        }
    }
    updateTotal();
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateTotal() {
    const svcSelect = document.getElementById('edit_service_id');
    const price  = parseFloat(svcSelect.options[svcSelect.selectedIndex]?.dataset.price || 0);
    const weight = parseFloat(document.getElementById('edit_weight').value || 0);
    const total  = price * weight;
    document.getElementById('edit_total_preview').textContent =
        total > 0 ? 'Rp ' + total.toLocaleString('id-ID') : '—';
}

document.addEventListener('DOMContentLoaded', function () {
    const svcSel = document.getElementById('edit_service_id');
    const wgt    = document.getElementById('edit_weight');
    if (svcSel) svcSel.addEventListener('change', updateTotal);
    if (wgt)    wgt.addEventListener('input', updateTotal);
});

// ── Validasi Nama (Tidak boleh angka, awal huruf besar) ───────
function formatNamaEdit(el) {
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

function blockAngkaNamaEdit(e) {
    const key = e.key;
    if (/^[0-9]$/.test(key) || (e.keyCode >= 96 && e.keyCode <= 105)) {
        e.preventDefault();
        const errEl = document.getElementById('editNamaError');
        if (errEl) {
            errEl.style.display = 'block';
            setTimeout(() => errEl.style.display = 'none', 2000);
        }
    }
}

// ── Validasi Nomor WA (Hanya boleh angka) ─────────────────────
function filterAngkaSajaEdit(el) {
    el.value = el.value.replace(/[^0-9]/g, '');
}

// Block keys that are not numbers or navigation keys
function blockHurufWAEdit(e) {
    const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
    if (allowed.includes(e.key) || /^[0-9]$/.test(e.key)) return;
    e.preventDefault();
}
