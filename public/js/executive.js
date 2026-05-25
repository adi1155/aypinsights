window.formatCurrency = (value, currency = 'PKR') => {
    if (value == null || isNaN(value)) return value;
    return `${currency} ${Number(value).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`;
};

window.initApexChart = (elementId, options) => {
    const el = document.querySelector(`#${elementId}`);
    if (!el || typeof ApexCharts === 'undefined') return null;
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const chart = new ApexCharts(el, {
        chart: { background: 'transparent', toolbar: { show: false }, animations: { enabled: true } },
        theme: { mode: isLight ? 'light' : 'dark' },
        grid: { borderColor: 'rgba(148,163,184,0.1)' },
        ...options,
    });
    chart.render();
    return chart;
};
