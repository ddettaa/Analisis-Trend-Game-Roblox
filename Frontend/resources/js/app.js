import ApexCharts from 'apexcharts';
// BlatUI engine (published via `vendor:publish --tag=blatui-foundations`) — imports Alpine,
// registers BlatUI's plugins/store/components, and calls Alpine.start() itself.
// NOTE: do NOT also import/start Alpine manually here — blatui.js already does this
// (see resources/js/blatui.js), so a duplicate Alpine.plugin()/Alpine.start() call here
// would double-register the anchor/collapse/focus plugins.
import './blatui';

window.ApexCharts = ApexCharts;

// ── Tema: default ikut sistem, persist di localStorage ──
if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}

// ── Chart registry theme-aware ──
const isDark = () => document.documentElement.classList.contains('dark');
const chartTheme = () => ({ mode: isDark() ? 'dark' : 'light', foreColor: isDark() ? '#a1a1aa' : '#52525b' });
window._charts = [];
window.registerChart = (el, options) => {
    const t = chartTheme();
    const c = new ApexCharts(el, {
        ...options,
        theme: { mode: t.mode },
        chart: { ...(options.chart || {}), background: 'transparent', foreColor: t.foreColor },
    });
    c.render();
    window._charts.push(c);
    return c;
};
new MutationObserver(() => {
    const t = chartTheme();
    window._charts.forEach(c => c.updateOptions({ theme: { mode: t.mode }, chart: { foreColor: t.foreColor } }));
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
