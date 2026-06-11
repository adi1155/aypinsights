import './bootstrap';

window.formatCurrency = (value, currency = 'PKR') => {
    if (value == null || isNaN(value)) return value;
    return `${currency} ${Number(value).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`;
};

window.__aypCharts = window.__aypCharts || [];

window.initApexChart = (elementId, options) => {
    const el = document.querySelector(`#${elementId}`);
    if (!el || typeof ApexCharts === 'undefined') return null;
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const chart = new ApexCharts(el, {
        chart: { background: 'transparent', toolbar: { show: false }, animations: { enabled: true } },
        theme: { mode: isLight ? 'light' : 'dark' },
        grid: { borderColor: isLight ? 'rgba(148,163,184,0.35)' : 'rgba(148,163,184,0.1)' },
        ...options,
    });
    chart.render();
    window.__aypCharts.push(chart);
    return chart;
};

window.addEventListener('ayp-theme-change', (e) => {
    const isLight = e.detail === 'light';
    window.__aypCharts.forEach((chart) => {
        chart.updateOptions({
            theme: { mode: isLight ? 'light' : 'dark' },
            grid: { borderColor: isLight ? 'rgba(148,163,184,0.35)' : 'rgba(148,163,184,0.1)' },
        });
    });
});

window.createDataTable = (rows, columns, title, perPage = 10) => ({
    rows: Array.isArray(rows) ? rows : [],
    columns: Array.isArray(columns) ? columns : [],
    title: title || 'Table',
    perPage: Math.max(1, Number(perPage) || 10),
    page: 1,

    get totalPages() {
        return Math.max(1, Math.ceil(this.rows.length / this.perPage));
    },

    get paginatedRows() {
        const start = (this.page - 1) * this.perPage;
        return this.rows.slice(start, start + this.perPage);
    },

    get rangeStart() {
        if (this.rows.length === 0) return 0;
        return (this.page - 1) * this.perPage + 1;
    },

    get rangeEnd() {
        return Math.min(this.page * this.perPage, this.rows.length);
    },

    get pageNumbers() {
        const pages = [];
        const maxButtons = 5;
        let start = Math.max(1, this.page - Math.floor(maxButtons / 2));
        let end = Math.min(this.totalPages, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);
        for (let p = start; p <= end; p++) pages.push(p);
        return pages;
    },

    goToPage(p) {
        this.page = Math.min(this.totalPages, Math.max(1, p));
    },

    rowValues(row) {
        return Array.isArray(row) ? row : [row];
    },

    formatCell(cell) {
        if (cell == null || cell === '') return '—';
        if (typeof cell === 'number' && Math.abs(cell) > 999) {
            return Number(cell).toLocaleString('en-PK', { maximumFractionDigits: 0 });
        }
        return String(cell);
    },

    exportExcel() {
        if (!this.rows.length) return;

        const escape = (value) => {
            const s = value == null ? '' : String(value);
            return /[",\n\r]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
        };

        const lines = [
            this.columns.map(escape).join(','),
            ...this.rows.map((row) => this.rowValues(row).map(escape).join(',')),
        ];

        const blob = new Blob(['\ufeff' + lines.join('\r\n')], {
            type: 'application/vnd.ms-excel;charset=utf-8;',
        });

        const safeName = this.title
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '') || 'table-export';

        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${safeName}-${new Date().toISOString().slice(0, 10)}.xls`;
        link.click();
        URL.revokeObjectURL(link.href);
    },
});

/** Call when all dashboard charts on the page have been initialized */
window.notifyDashboardReady = function () {
    if (window.__dashboardReadyFired) {
        return;
    }
    window.__dashboardReadyFired = true;
    window.dispatchEvent(new CustomEvent('dashboard:ready'));
};
