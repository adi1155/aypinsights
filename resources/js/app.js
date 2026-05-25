import './bootstrap';

window.formatCurrency = (value, currency = 'PKR') => {
    if (value == null || isNaN(value)) return value;
    return `${currency} ${Number(value).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`;
};

window.initApexChart = (elementId, options) => {
    const el = document.querySelector(`#${elementId}`);
    if (!el || typeof ApexCharts === 'undefined') return null;
    const chart = new ApexCharts(el, {
        chart: { background: 'transparent', toolbar: { show: false }, animations: { enabled: true } },
        theme: { mode: document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark' },
        grid: { borderColor: 'rgba(148,163,184,0.1)' },
        ...options,
    });
    chart.render();
    return chart;
};

/** Call when all dashboard charts on the page have been initialized */
window.notifyDashboardReady = function () {
    if (window.__dashboardReadyFired) {
        return;
    }
    window.__dashboardReadyFired = true;
    window.dispatchEvent(new CustomEvent('dashboard:ready'));
};
