import ApexCharts from 'apexcharts';
import './blatui';

window.ApexCharts = ApexCharts;

const isDark = () => document.documentElement.classList.contains('dark');
const cssToken = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
const chartTheme = () => ({
    mode: isDark() ? 'dark' : 'light',
    foreColor: cssToken('--muted-foreground', '#52525b'),
    palette: [
        cssToken('--chart-1', '#2563eb'),
        cssToken('--chart-2', '#1d4ed8'),
        cssToken('--chart-3', '#0891b2'),
        cssToken('--chart-4', '#059669'),
        cssToken('--chart-5', '#d97706'),
    ],
    grid: cssToken('--border', '#e4e4e7'),
});

const syncColorScheme = () => {
    document.documentElement.style.colorScheme = chartTheme().mode;
};

window._charts = [];
const chartMetadata = new WeakMap();
const hasOwn = (object, property) => Object.prototype.hasOwnProperty.call(object, property);

window.registerChart = (el, options = {}) => {
    if (!el) return null;

    const theme = chartTheme();
    const autoColors = !hasOwn(options, 'colors');
    const autoGrid = !hasOwn(options.grid || {}, 'borderColor');
    const chart = new ApexCharts(el, {
        ...options,
        ...(autoColors ? { colors: theme.palette } : {}),
        theme: { ...(options.theme || {}), mode: theme.mode },
        chart: {
            ...(options.chart || {}),
            background: 'transparent',
            foreColor: theme.foreColor,
            fontFamily: 'inherit',
        },
        grid: { ...(options.grid || {}), ...(autoGrid ? { borderColor: theme.grid } : {}) },
    });

    chart.render();
    window._charts.push(chart);
    chartMetadata.set(chart, { autoColors, autoGrid });
    return chart;
};

new MutationObserver(() => {
    const theme = chartTheme();
    syncColorScheme();
    window._charts.forEach(chart => {
        const metadata = chartMetadata.get(chart);
        const updates = {
            theme: { mode: theme.mode },
            chart: { foreColor: theme.foreColor },
        };

        if (metadata?.autoColors) updates.colors = theme.palette;
        if (metadata?.autoGrid) updates.grid = { borderColor: theme.grid };

        chart.updateOptions(updates);
    });
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

syncColorScheme();
