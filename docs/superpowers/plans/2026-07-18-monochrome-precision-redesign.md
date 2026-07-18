# Monochrome Precision Frontend Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the landing page and analytics dashboard as a compact, premium, dark-first monochrome SaaS product while preserving every existing route, data contract, fallback, and accessibility guarantee.

**Architecture:** Keep Laravel controllers and `FastApiClient` unchanged. Replace the editorial presentation layer with reusable SaaS Blade components, neutral theme tokens, and a small landing-only motion module. BlatUI remains the component foundation, ApexCharts remains behind `registerChart()`, and Playwright adds true viewport/theme smoke coverage.

**Tech Stack:** Laravel 13, Blade, BlatUI, Alpine.js, Tailwind CSS v4, ApexCharts, Motion, Lenis, Fontsource Variable, PHPUnit, Node test runner, Playwright, Vite.

---

## Constraints

- Work only in `Frontend/` plus docs; Backend and analytics calculations remain untouched.
- Preserve `/`, `/dashboard`, `/dashboard/saturasi`, `/dashboard/viral`.
- Preserve controller variables, escaped Blade table values, and all API fallback wording.
- Keep `registerChart()` as the only ApexCharts constructor.
- Do not restore ASCII backgrounds, saturated blue/red sections, or oversized dashboard headings.
- Preserve the user’s notebook and unrelated untracked files.

## File map

```text
Frontend/
├── package.json / package-lock.json                # Fontsource, Motion, Lenis, Playwright
├── resources/css/app.css                           # Neutral dark/light SaaS tokens and primitives
├── resources/js/app.js                             # Font imports and landing initializer
├── resources/js/landing-experience.js              # Motion + Lenis lifecycle
├── resources/js/motion-preferences.js              # Pure reduced-motion decision
├── resources/views/components/analytics/           # SaaS brand, shell, cards, nav
├── resources/views/layouts/app.blade.php            # Command bar + icon rail + mobile tabs
├── resources/views/landing.blade.php                # Product-led landing
├── resources/views/dashboard/*.blade.php            # Compact analytics workspaces
├── tests/JavaScript/motion-preferences.test.mjs     # Motion decision tests
├── tests/Feature/{LandingTest,DashboardTest}.php    # Render contracts
├── tests/Browser/monochrome-smoke.spec.js           # Theme/viewport smoke tests
└── playwright.config.js                             # Existing Chrome, local Laravel server
```

### Task 1: Dependencies, typography, neutral tokens, and motion runtime

**Files:**
- Modify: `Frontend/package.json`, `Frontend/package-lock.json`
- Modify: `Frontend/resources/css/app.css`
- Modify: `Frontend/resources/js/app.js`
- Create: `Frontend/resources/js/motion-preferences.js`
- Create: `Frontend/resources/js/landing-experience.js`
- Create: `Frontend/tests/JavaScript/motion-preferences.test.mjs`

- [ ] **Step 1: Install exact dependencies**

```powershell
cd Frontend
npm install -D @fontsource-variable/inter @fontsource-variable/jetbrains-mono motion lenis @playwright/test
```

- [ ] **Step 2: Write and run the failing motion test**

```js
import test from 'node:test';
import assert from 'node:assert/strict';
import { shouldEnableMotion } from '../../resources/js/motion-preferences.js';

test('motion is disabled when reduced motion is requested', () => assert.equal(shouldEnableMotion(true), false));
test('motion is enabled when reduced motion is not requested', () => assert.equal(shouldEnableMotion(false), true));
```

Run `node --test tests/JavaScript/motion-preferences.test.mjs`. Expected: `ERR_MODULE_NOT_FOUND`.

- [ ] **Step 3: Implement the motion preference and landing lifecycle**

```js
// resources/js/motion-preferences.js
export const shouldEnableMotion = (reducedMotion) => !reducedMotion;
```

```js
// resources/js/landing-experience.js
import Lenis from 'lenis';
import { animate, inView, stagger } from 'motion';
import { shouldEnableMotion } from './motion-preferences';

export function initLandingExperience() {
    const page = document.querySelector('[data-page="landing"]');
    if (!page) return () => {};
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    if (!shouldEnableMotion(reduce)) return () => {};

    const lenis = new Lenis({ duration: 1.05, smoothWheel: true });
    let frame;
    const raf = (time) => { lenis.raf(time); frame = requestAnimationFrame(raf); };
    frame = requestAnimationFrame(raf);

    animate('[data-hero-item]', { opacity: [0, 1], y: [18, 0] }, { duration: .55, delay: stagger(.08) });
    const stops = [...document.querySelectorAll('[data-reveal]')].map((element) =>
        inView(element, () => animate(element, { opacity: [0, 1], y: [16, 0] }, { duration: .45 }), { margin: '-10% 0px' })
    );
    return () => { cancelAnimationFrame(frame); lenis.destroy(); stops.forEach((stop) => stop()); };
}
```

Import the two Fontsource CSS entry points and `initLandingExperience` in `app.js`, then initialize on `DOMContentLoaded`. Keep the chart registry and BlatUI import intact.

- [ ] **Step 4: Replace editorial CSS with monochrome SaaS tokens**

Keep the BlatUI import and Tailwind sources. Override root/dark tokens with warm white/graphite neutral values, `--font-sans: 'Inter Variable'`, `--font-mono: 'JetBrains Mono Variable'`, 10px radius, neutral five-step chart palette, hairline borders, and one subtle shadow scale. Add reusable classes:

```css
.saas-container { width: min(100% - 2rem, 80rem); margin-inline: auto; }
.saas-panel { border: 1px solid var(--border); border-radius: .75rem; background: var(--card); }
.saas-label { font: 600 .6875rem/1 var(--font-mono); letter-spacing: .08em; text-transform: uppercase; color: var(--muted-foreground); }
.saas-title { font-size: clamp(1.75rem, 3vw, 2.25rem); line-height: 1.1; letter-spacing: -.04em; font-weight: 650; }
.landing-display { font-size: clamp(3.25rem, 7vw, 4.75rem); line-height: .98; letter-spacing: -.065em; font-weight: 650; }
[data-reveal] { opacity: 0; }
```

Under reduced motion, force `[data-reveal]` visible and retain the existing global animation override.

- [ ] **Step 5: Verify and commit**

Run Node tests, `npm run build`, and `php artisan test`. Commit:

```powershell
git add Frontend/package*.json Frontend/resources/css/app.css Frontend/resources/js Frontend/tests/JavaScript
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "feat: add monochrome SaaS foundation and landing motion"
```

### Task 2: SaaS component set and responsive dashboard shell

**Files:**
- Modify: `Frontend/resources/views/components/analytics/*.blade.php`
- Modify: `Frontend/resources/views/layouts/app.blade.php`
- Modify: `Frontend/tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add failing shell contracts**

Extend the overview response assertions with raw markers:

```php
->assertSee('data-shell="saas-dashboard"', false)
->assertSee('data-ui="command-bar"', false)
->assertSee('data-ui="icon-rail"', false)
->assertSee('Market overview');
```

Run the focused test and confirm failure.

- [ ] **Step 2: Redesign shared components**

- `brand-mark`: compact RS tile plus “Roblox Signals” wordmark.
- `theme-toggle`: 40×40 BlatUI ghost control with stable accessible label.
- `dashboard-nav`: 64px desktop icon rail with tooltips/titles and safe-area mobile bottom tabs.
- `metric-card`: compact label/value/optional trend layout using `saas-panel`.
- `page-heading`: compact eyebrow, 28–36px title, description, optional action slot.
- `status-panel`: preserve all existing wording and BlatUI tones.

Every component must merge attributes and expose stable `data-ui` markers.

- [ ] **Step 3: Replace the shell**

Use a sticky 56px `data-ui="command-bar"`, a `data-ui="icon-rail"`, `data-shell="saas-dashboard"`, compact snapshot metadata, max-width content, `lg:pl-16`, and mobile padding `calc(5rem + env(safe-area-inset-bottom))`. Preserve early theme bootstrap, `@vite`, status panel, and `@yield`.

- [ ] **Step 4: Verify and commit**

Run focused DashboardTest, full suite, and build. Commit `feat: rebuild dashboard shell as monochrome SaaS`.

### Task 3: Product-led landing page

**Files:**
- Modify: `Frontend/resources/views/landing.blade.php`
- Modify: `Frontend/tests/Feature/LandingTest.php`

- [ ] **Step 1: Add failing product structure contracts**

Successful landing must assert:

```php
->assertSee('See the market before it moves')
->assertSee('data-ui="product-preview"', false)
->assertSee('data-section="proof"', false)
->assertSee('data-section="features"', false)
->assertSee('data-section="methodology"', false);
```

Keep full-down, partial-down, and empty-ranking tests.

- [ ] **Step 2: Rebuild landing in this exact order**

Sticky header; two-column hero with product preview; proof strip; three feature bento cards; live snapshot KPI/chart section; methodology timeline; final CTA; footer. Use `data-hero-item` and `data-reveal` hooks. Use only grayscale surfaces and neutral chart colors. Preserve real data, chart textual equivalents, status panels, and `registerChart()`.

Product preview shows server-rendered snapshot values and a decorative mini bar visualization. It must not introduce a second chart instance.

- [ ] **Step 3: Verify and commit**

Run LandingTest, full suite, Node tests, and build. Commit `feat: rebuild landing as product-led SaaS experience`.

### Task 4: Compact analytics workspaces

**Files:**
- Modify: `Frontend/resources/views/dashboard/ringkasan.blade.php`
- Modify: `Frontend/resources/views/dashboard/saturasi.blade.php`
- Modify: `Frontend/resources/views/dashboard/viral.blade.php`
- Modify: `Frontend/tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add failing page contracts**

Assert compact titles `Market overview`, `Market saturation`, `Early momentum`, page-specific `data-workspace`, `data-ui="kpi-grid"`, and chart/table panel markers. Preserve active-nav, row-header, fallback, and content assertions.

- [ ] **Step 2: Rebuild all pages**

Use the shared compact heading and metric components. Ringkasan uses a 3-column KPI grid, 5/3 chart grid, and dense table. Saturasi uses legend, primary chart, and table. Viral uses one count KPI, horizontal chart, and table. All use `saas-panel`, real heading hierarchy, accessible equivalents, conditional chart mounts, and existing data fields/options.

- [ ] **Step 3: Verify and commit**

Run DashboardTest, full suite, Node tests, and build. Commit `feat: refine analytics pages with compact SaaS layout`.

### Task 5: Playwright responsive/theme smoke and final verification

**Files:**
- Create: `Frontend/playwright.config.js`
- Create: `Frontend/tests/Browser/monochrome-smoke.spec.js`

- [ ] **Step 1: Configure installed Chrome and local server**

```js
import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: './tests/Browser',
    use: { baseURL: 'http://127.0.0.1:8001', channel: 'chrome', screenshot: 'only-on-failure' },
    webServer: { command: 'php artisan serve --host=127.0.0.1 --port=8001', url: 'http://127.0.0.1:8001', reuseExistingServer: true },
});
```

- [ ] **Step 2: Add smoke tests**

For each route, test 1440×1000 and 390×844, set `theme:mode` to dark/light before reload, assert no horizontal overflow, visible page title, correct navigation, and safe bottom-tab bounds on mobile.

- [ ] **Step 3: Run final gates**

```powershell
node --test tests/JavaScript/*.test.mjs
npm run build
php artisan test
npx playwright test
rg -n "cdn\.jsdelivr\.net/npm/apexcharts|new ApexCharts" resources/views
git diff --check
```

Expected: all commands pass and the forbidden Apex scan has no matches. Commit Playwright files only if tests pass.
