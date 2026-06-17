// ============================================================
//  public/js/payment.js — Script Halaman Pembayaran
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const amountInput   = document.getElementById('amountPaid');
    const kembalianBox  = document.getElementById('kembalianBox');
    const kembalianAmt  = document.getElementById('kembalianAmt');
    const totalPriceEl  = document.getElementById('totalPriceData');

    if (!amountInput) return;

    const totalPrice = totalPriceEl ? parseFloat(totalPriceEl.dataset.total) : 0;

    function updateKembalian() {
        const isCash = document.querySelector('input[name="pay_method"]:checked')?.value === 'Cash';
        const paid   = parseFloat(amountInput.value || 0);
        if (isCash && kembalianBox) {
            kembalianBox.style.display = 'block';
            kembalianAmt.textContent = 'Rp ' + Math.max(0, paid - totalPrice).toLocaleString('id-ID');
        } else if (kembalianBox) {
            kembalianBox.style.display = 'none';
        }
    }

    document.querySelectorAll('input[name="pay_method"]').forEach(r => r.addEventListener('change', updateKembalian));
    amountInput.addEventListener('input', updateKembalian);
    updateKembalian();
});
