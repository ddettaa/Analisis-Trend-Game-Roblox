import ApexCharts from 'apexcharts';
import './blatui';

window.ApexCharts = ApexCharts;

const isDark = () => document.documentElement.classList.contains('dark');
const cssToken = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
const chartTheme = () => ({
    mode: isDark() ? 'dark' : 'light',
    foreColor: cssToken('--muted-foreground', '#52525b'),
    primary: cssToken('--chart-1', '#2563eb'),
    border: cssToken('--border', '#e4e4e7'),
});

window._charts = [];
window.registerChart = (el, options) => {
    if (!el) return null;

    const theme = chartTheme();
    const chart = new ApexCharts(el, {
        ...options,
        colors: options.colors || [theme.primary],
        theme: { ...(options.theme || {}), mode: theme.mode },
        chart: {
            ...(options.chart || {}),
            background: 'transparent',
            foreColor: theme.foreColor,
            fontFamily: 'inherit',
        },
        grid: { ...(options.grid || {}), borderColor: theme.border },
    });

    chart.render();
    window._charts.push(chart);
    return chart;
};

new MutationObserver(() => {
    const theme = chartTheme();
    window._charts.forEach(chart =>
        chart.updateOptions({
            colors: [theme.primary],
            theme: { mode: theme.mode },
            chart: { foreColor: theme.foreColor },
            grid: { borderColor: theme.border },
        }),
    );
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
