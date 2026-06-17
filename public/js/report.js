// ============================================================
//  public/js/report.js — Script Chart Laporan Bulanan
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const chartDataEl = document.getElementById('chartData');
    if (!chartDataEl) return;

    const chartLabels = JSON.parse(chartDataEl.dataset.labels  || '[]');
    const chartData   = JSON.parse(chartDataEl.dataset.values  || '[]');
    const svcLabels   = JSON.parse(chartDataEl.dataset.svclabels || '[]');
    const svcData     = JSON.parse(chartDataEl.dataset.svcvalues || '[]');
    const colors      = ['#4fc3a1','#1a3c5e','#2563a8','#f59e0b','#ec4899'];

    if (chartLabels.length > 0) {
        new Chart(document.getElementById('dailyChart'), {
            type: 'bar',
            data: {
                labels: chartLabels.map(d => 'Tgl ' + d),
                datasets: [{ label: 'Pendapatan (Rp)', data: chartData, backgroundColor: '#4fc3a1', borderRadius: 6 }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } }
            }
        });
    }

    if (svcLabels.length > 0) {
        new Chart(document.getElementById('serviceChart'), {
            type: 'doughnut',
            data: {
                labels: svcLabels,
                datasets: [{ data: svcData, backgroundColor: colors, borderWidth: 2 }]
            },
            options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } } }
        });
    }
});
