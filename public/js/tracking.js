// ============================================================
//  public/js/tracking.js — Script Halaman Tracking Pelanggan
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const invoiceInput = document.getElementById('invoiceInput');
    if (invoiceInput) {
        invoiceInput.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    }
});
