/* ============================================================
   assets/js/admin.js — Dashboard admin tables, charts, exports
   ============================================================ */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initSortableTables();
    initSelectAll();
    initCsvExport();
    initCharts();
    initSearchFilter();
});

// ============================================================
// Sortable Table Headers
// ============================================================
function initSortableTables() {
    document.querySelectorAll('table th[data-sort]').forEach((th, index) => {
        th.style.cursor = 'pointer';
        th.innerHTML += ' <i class="ri-arrow-up-down-line" style="font-size:0.75rem;opacity:0.4"></i>';
        th.addEventListener('click', () => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));
            const asc   = th.dataset.sortDir !== 'asc';
            th.dataset.sortDir = asc ? 'asc' : 'desc';

            table.querySelectorAll('th[data-sort]').forEach(t => {
                t.querySelector('i').style.opacity = '0.4';
            });
            th.querySelector('i').style.opacity = '1';

            rows.sort((a, b) => {
                const aVal = a.cells[index]?.textContent.trim() || '';
                const bVal = b.cells[index]?.textContent.trim() || '';
                return asc
                    ? aVal.localeCompare(bVal, undefined, { numeric: true })
                    : bVal.localeCompare(aVal, undefined, { numeric: true });
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });
}

// ============================================================
// Select All Checkboxes
// ============================================================
function initSelectAll() {
    document.querySelectorAll('#selectAll').forEach(selectAll => {
        selectAll.addEventListener('change', () => {
            const table = selectAll.closest('table');
            table.querySelectorAll('.row-check').forEach(cb => cb.checked = selectAll.checked);
        });
    });
}

// ============================================================
// CSV Export
// ============================================================
function initCsvExport() {
    document.querySelectorAll('[data-export-csv]').forEach(btn => {
        btn.addEventListener('click', () => {
            const tableId  = btn.dataset.exportCsv;
            const filename = (btn.dataset.filename || 'export') + '_' + new Date().toISOString().slice(0,10) + '.csv';
            const table    = document.getElementById(tableId);
            if (!table) { showToast('Table not found', 'error'); return; }

            const rows = [];
            // Headers (skip checkbox column)
            const headers = Array.from(table.querySelectorAll('thead th')).slice(1, -1).map(th => th.textContent.trim().replace(/[\n\r\t]/g,''));
            rows.push(headers.join(','));

            // Body
            table.querySelectorAll('tbody tr').forEach(tr => {
                const cells = Array.from(tr.querySelectorAll('td')).slice(1, -1).map(td => {
                    const text = td.textContent.trim().replace(/[\n\r\t]+/g,' ');
                    return '"' + text.replace(/"/g, '""') + '"';
                });
                rows.push(cells.join(','));
            });

            const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url; a.download = filename; a.click();
            URL.revokeObjectURL(url);
            showToast('CSV exported!', 'success');
        });
    });
}

// ============================================================
// Charts (Chart.js) — fires only if Chart is loaded
// ============================================================
function initCharts() {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color       = getComputedStyle(document.documentElement).getPropertyValue('--color-text-2') || '#475569';

    // Line/Bar chart
    const eventsCanvas = document.getElementById('eventsChart');
    if (eventsCanvas) {
        const labels = JSON.parse(eventsCanvas.dataset.labels || '[]');
        const values = JSON.parse(eventsCanvas.dataset.values || '[]');
        // Fill parent wrapper
        eventsCanvas.style.width  = '100%';
        eventsCanvas.style.height = '100%';
        new Chart(eventsCanvas, {
            type: 'bar',
            data: {
                labels: labels.length ? labels : ['Jan','Feb','Mar','Apr','May','Jun'],
                datasets: [{
                    label: 'Registrations',
                    data: values.length ? values : [0,0,0,0,0,0],
                    backgroundColor: 'rgba(26,86,219,0.15)',
                    borderColor: '#1a56db',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0 }, max: Math.max(...(values.length ? values : [1])) + 2 },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Doughnut chart
    const membersCanvas = document.getElementById('membersChart');
    if (membersCanvas) {
        const labels = JSON.parse(membersCanvas.dataset.labels || '[]');
        const values = JSON.parse(membersCanvas.dataset.values || '[]');
        const colors = ['#1a56db','#7c3aed','#059669','#d97706','#e11d48','#0891b2'];
        // Fill parent wrapper
        membersCanvas.style.width  = '100%';
        membersCanvas.style.height = '100%';
        new Chart(membersCanvas, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [{
                    data: values.length ? values : [1],
                    backgroundColor: values.length ? colors.slice(0, values.length) : ['#e2e8f0'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 }, boxWidth: 12 } }
                },
                cutout: '65%',
            }
        });
    }
}

// ============================================================
// Live Search Filter (client-side table search)
// ============================================================
function initSearchFilter() {
    const searchInput = document.querySelector('.search-bar input[type="text"]');
    const table       = document.querySelector('.table tbody');
    if (!searchInput || !table) return;

    // Only filter client-side if there's no server search (no form action)
    const form = searchInput.closest('form');
    if (form) return; // Server-side — let form submit handle it

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase();
        table.querySelectorAll('tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
