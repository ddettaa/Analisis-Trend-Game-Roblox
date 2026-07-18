import { expect, test } from '@playwright/test';

const routes = [
    {
        path: '/',
        heading: 'See the market before it moves',
        kind: 'landing',
    },
    {
        path: '/dashboard',
        heading: 'Market overview',
        kind: 'dashboard',
        activePage: 'ringkasan',
        workspace: 'ringkasan',
        panels: ['overview-chart-card', 'overview-table-card'],
        charts: [
            {
                id: 'rankingchart',
                panel: 'overview-chart-card',
                panelHeading: 'Rata-rata Pemain Aktif per Genre',
                fallback: 'Belum ada data ranking.',
            },
            {
                id: 'sharechart',
                panel: 'overview-chart-card',
                panelHeading: 'Komposisi Genre (%)',
                fallback: 'Belum ada data komposisi genre.',
            },
        ],
    },
    {
        path: '/dashboard/saturasi',
        heading: 'Market saturation',
        kind: 'dashboard',
        activePage: 'saturasi',
        workspace: 'saturasi',
        panels: ['saturation-chart-card', 'saturation-table-card'],
        charts: [
            {
                id: 'satchart',
                panel: 'saturation-chart-card',
                panelHeading: 'Jumlah Game per Genre',
                fallback: 'Belum ada data saturasi.',
            },
        ],
    },
    {
        path: '/dashboard/viral',
        heading: 'Early momentum',
        kind: 'dashboard',
        activePage: 'viral',
        workspace: 'viral',
        panels: ['viral-chart-card', 'viral-table-card'],
        charts: [
            {
                id: 'viralchart',
                panel: 'viral-chart-card',
                panelHeading: 'Kecepatan Pertumbuhan Pemain',
                fallback: 'Belum ada game viral muda.',
            },
        ],
    },
];

const viewports = [
    { name: 'desktop', width: 1440, height: 1000, themes: ['dark', 'light'] },
    { name: 'mobile', width: 390, height: 844, themes: ['dark', 'light'] },
    { name: 'compact', width: 320, height: 844, themes: ['dark'] },
];

const collectBrowserErrors = (page) => {
    const errors = [];

    page.on('pageerror', (error) => errors.push(`pageerror: ${error.stack ?? error.message}`));
    page.on('console', (message) => {
        if (message.type() === 'error') {
            const location = message.location();
            const source = location.url ? ` (${location.url}:${location.lineNumber})` : '';
            errors.push(`console: ${message.text()}${source}`);
        }
    });

    return errors;
};

const assertTheme = async (page, theme) => {
    const root = page.locator('html');

    if (theme === 'dark') {
        await expect(root).toHaveClass(/(?:^|\s)dark(?:\s|$)/);
    } else {
        await expect(root).not.toHaveClass(/(?:^|\s)dark(?:\s|$)/);
    }

    await expect(root).toHaveCSS('color-scheme', theme);
    await expect.poll(() => page.evaluate(() => localStorage.getItem('theme:mode'))).toBe(theme);
};

const assertNoPageOverflow = async (page) => {
    const dimensions = await page.evaluate(() => ({
        documentClientWidth: document.documentElement.clientWidth,
        documentScrollWidth: document.documentElement.scrollWidth,
        bodyClientWidth: document.body.clientWidth,
        bodyScrollWidth: document.body.scrollWidth,
    }));

    expect(dimensions.documentScrollWidth, JSON.stringify(dimensions)).toBeLessThanOrEqual(dimensions.documentClientWidth + 1);
    expect(dimensions.bodyScrollWidth, JSON.stringify(dimensions)).toBeLessThanOrEqual(dimensions.bodyClientWidth + 1);
};

const assertDashboardNavigation = async (page, route, viewport) => {
    const navigation = page.locator('[data-ui="dashboard-nav"]');
    const iconRail = navigation.locator('[data-ui="icon-rail"]');
    const mobileNavigation = navigation.locator('[data-ui="mobile-dashboard-nav"]');
    const activeLinks = navigation.locator(`a[data-page="${route.activePage}"][aria-current="page"]`);
    const expectedPath = route.path;

    await expect(navigation).toBeAttached();
    await expect(activeLinks).toHaveCount(2);
    for (const link of await activeLinks.all()) {
        await expect(link).toHaveAttribute('href', expectedPath);
    }
    await expect(navigation.locator('a[aria-current="page"]:not([data-page="' + route.activePage + '"])')).toHaveCount(0);

    if (viewport.name === 'desktop') {
        await expect(iconRail).toBeVisible();
        await expect(mobileNavigation).toBeHidden();
        await expect(iconRail.locator(`a[data-page="${route.activePage}"]`)).toBeVisible();
        return;
    }

    await expect(iconRail).toBeHidden();
    await expect(mobileNavigation).toBeVisible();
    await expect(mobileNavigation.locator('a')).toHaveCount(3);
    await expect(mobileNavigation.locator(`a[data-page="${route.activePage}"]`)).toBeVisible();

    const geometry = await page.evaluate(() => {
        const nav = document.querySelector('[data-ui="mobile-dashboard-nav"]');
        const content = document.querySelector('main > div');
        const navBox = nav.getBoundingClientRect();

        return {
            nav: { x: navBox.x, y: navBox.y, width: navBox.width, height: navBox.height, bottom: navBox.bottom },
            contentPaddingBottom: Number.parseFloat(getComputedStyle(content).paddingBottom),
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
        };
    });

    expect(geometry.nav.x).toBeGreaterThanOrEqual(-1);
    expect(geometry.nav.y).toBeGreaterThanOrEqual(0);
    expect(geometry.nav.x + geometry.nav.width).toBeLessThanOrEqual(geometry.viewportWidth + 1);
    expect(geometry.nav.bottom).toBeLessThanOrEqual(geometry.viewportHeight + 1);
    expect(geometry.nav.bottom).toBeGreaterThanOrEqual(geometry.viewportHeight - 1);
    expect(geometry.contentPaddingBottom).toBeGreaterThanOrEqual(geometry.nav.height);
};

const assertChartOrFallback = async (page, chart, viewport) => {
    const panel = page.locator(`[data-ui="${chart.panel}"]`).filter({
        has: page.getByRole('heading', { name: chart.panelHeading, exact: true }),
    });
    const target = panel.locator(`#${chart.id}`);

    await expect(panel).toHaveCount(1);
    await expect(panel).toBeVisible();

    if ((await target.count()) === 0) {
        await expect(panel.getByText(chart.fallback, { exact: true })).toBeVisible();
        return;
    }

    await expect(target).toBeVisible();
    const canvas = target.locator('.apexcharts-canvas');
    await expect(canvas).toHaveCount(1);
    await expect(canvas).toBeVisible();
    const renderedGraphic = canvas.locator('svg.apexcharts-svg, canvas');
    await expect(renderedGraphic).toHaveCount(1);
    await expect(renderedGraphic).toBeVisible();

    const geometry = await page.evaluate(({ targetId, panelUi, heading }) => {
        const targetElement = document.getElementById(targetId);
        const matchingPanel = [...document.querySelectorAll(`[data-ui="${panelUi}"]`)].find((candidate) =>
            [...candidate.querySelectorAll('h1, h2, h3, h4, h5, h6')].some((element) => element.textContent.trim() === heading),
        );
        const canvasElement = targetElement.querySelector('.apexcharts-canvas');
        const graphicElement = canvasElement.querySelector('svg.apexcharts-svg, canvas');
        const panelBox = matchingPanel.getBoundingClientRect();
        const canvasBox = canvasElement.getBoundingClientRect();
        const graphicBox = graphicElement.getBoundingClientRect();

        return {
            panel: { left: panelBox.left, right: panelBox.right, width: matchingPanel.clientWidth },
            canvas: { left: canvasBox.left, right: canvasBox.right, width: canvasBox.width },
            graphic: { left: graphicBox.left, right: graphicBox.right, width: graphicBox.width },
            viewportWidth: document.documentElement.clientWidth,
        };
    }, { targetId: chart.id, panelUi: chart.panel, heading: chart.panelHeading });

    expect(geometry.canvas.left).toBeGreaterThanOrEqual(geometry.panel.left - 1);
    expect(geometry.canvas.right).toBeLessThanOrEqual(geometry.panel.right + 1);
    expect(geometry.canvas.right).toBeLessThanOrEqual(geometry.viewportWidth + 1);
    expect(geometry.graphic.left).toBeGreaterThanOrEqual(geometry.panel.left - 1);
    expect(geometry.graphic.right).toBeLessThanOrEqual(geometry.panel.right + 1);

    if (viewport.name !== 'desktop') {
        expect(geometry.canvas.width).toBeLessThanOrEqual(geometry.panel.width + 1);
        expect(geometry.graphic.width).toBeLessThanOrEqual(geometry.panel.width + 1);
    }
};

const assertLanding = async (page) => {
    await expect(page.locator('body > header')).toBeVisible();
    await expect(page.locator('[data-ui="product-preview"]')).toBeVisible();
    await expect(page.locator('[data-ui="dashboard-nav"], [data-ui="mobile-dashboard-nav"]')).toHaveCount(0);
    await expect(page.locator('[data-page][aria-current="page"]')).toHaveCount(0);

    await expect.poll(() => page.locator('main > section[data-section]').evaluateAll((sections) =>
        sections.map((section) => section.dataset.section),
    )).toEqual(['hero', 'proof', 'features', 'live-data', 'methodology', 'final-cta']);

    const accessibilityRegions = page.locator('[data-section="features"], [data-section="final-cta"]');
    await expect(accessibilityRegions.getByRole('img')).toHaveCount(0);
    await expect(accessibilityRegions.locator('svg:not([aria-hidden="true"])')).toHaveCount(0);
    await expect(page.locator('[data-section="hero"]').getByRole('link', { name: 'Lihat Dashboard', exact: true })).toBeVisible();
    await expect(page.locator('[data-section="final-cta"]').getByRole('link', { name: 'Buka Dashboard', exact: true })).toBeVisible();
};

const assertDashboard = async (page, route, viewport) => {
    await expect(page.locator('[data-ui="command-bar"]')).toBeVisible();
    await expect(page.locator(`[data-workspace="${route.workspace}"]`)).toBeVisible();
    await expect(page.locator('[data-ui="kpi-grid"]')).toBeVisible();
    await expect(page.locator('[data-ui="kpi-grid"] [data-ui="metric-card"]')).not.toHaveCount(0);

    for (const panel of route.panels) {
        const panels = page.locator(`[data-ui="${panel}"]`);
        await expect(panels.first()).toBeVisible();
    }

    await assertDashboardNavigation(page, route, viewport);
    for (const chart of route.charts) {
        await assertChartOrFallback(page, chart, viewport);
    }
};

for (const route of routes) {
    for (const viewport of viewports) {
        for (const theme of viewport.themes) {
            test(`${route.path} renders at ${viewport.width}x${viewport.height} in ${theme}`, async ({ page }) => {
                const browserErrors = collectBrowserErrors(page);

                await page.setViewportSize({ width: viewport.width, height: viewport.height });
                await page.addInitScript((mode) => localStorage.setItem('theme:mode', mode), theme);

                const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
                expect(response, 'navigation should produce an HTTP response').not.toBeNull();
                expect(response.status()).toBeGreaterThanOrEqual(200);
                expect(response.status()).toBeLessThan(300);
                await expect(page.getByRole('heading', { level: 1, name: route.heading, exact: true })).toBeVisible();
                await page.waitForLoadState('networkidle');
                await assertTheme(page, theme);

                if (route.kind === 'landing') {
                    await assertLanding(page);
                } else {
                    await assertDashboard(page, route, viewport);
                }

                await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
                await assertNoPageOverflow(page);
                expect(browserErrors, browserErrors.join('\n')).toEqual([]);
            });
        }
    }
}
