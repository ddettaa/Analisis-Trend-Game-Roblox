# Monochrome Precision Frontend Redesign

**Date:** 2026-07-18  
**Status:** Approved  
**Direction:** Premium SaaS, dark-first, inspired by Linear, Vercel, and Raycast

## Objective

Replace the current oversized editorial treatment with a mature product-led SaaS experience. The landing page must explain and preview the product instead of behaving like a poster. Dashboard pages must feel compact, calm, and data-first while retaining every existing route, API contract, fallback state, and accessibility guarantee.

## Visual System

- Dark mode uses neutral black and graphite surfaces with white typography and restrained gray hierarchy.
- Light mode uses warm white and pale gray surfaces with near-black typography.
- No saturated theme identity, large color fields, ASCII texture, or decorative gradients.
- One subtle radial highlight may appear behind the landing product preview; it remains monochrome.
- Inter Variable is the primary font. JetBrains Mono Variable is reserved for labels, metadata, and numeric utility text.
- Radius is consistent at 8–12px. Borders are one-pixel hairlines. Shadows are subtle and used only to separate elevated product previews.
- Typography follows conventional SaaS hierarchy: landing display 56–76px desktop, page titles 28–36px, body 14–18px, metadata 11–12px.

## Libraries

- Keep Laravel Blade, Alpine.js, BlatUI, Lucide icons, and ApexCharts.
- Add `@fontsource-variable/inter` and `@fontsource-variable/jetbrains-mono` for local fonts.
- Add `motion` for entrance/reveal/stagger animations.
- Add `lenis` for landing-page smooth scrolling only.
- Add `@playwright/test` for desktop/mobile and light/dark smoke verification using installed Chrome when available.
- Do not add GSAP or a second chart/component framework.

## Landing Page

1. Sticky translucent product header with compact RS mark, Product/Insights/Methodology anchors, theme control, and dashboard CTA.
2. Balanced two-column hero: benefit-led copy on the left and a realistic live dashboard preview on the right.
3. Proof strip listing Live Snapshots, Genre Demand, Market Saturation, and Early Momentum.
4. Bento feature section explaining demand, saturation, and viral discovery with compact data visuals.
5. Live-data section showing current snapshot metrics and a Top-5 genre chart.
6. Methodology section explaining the flow from Roblox discovery data to ranked market signals.
7. High-contrast final CTA and compact footer.

The hero, navigation, product explanation, and CTA always render when the API is unavailable. Data-dependent blocks use BlatUI status or empty-state components.

## Dashboard Shell

- Sticky 56px top command bar with brand, breadcrumb/search affordance, snapshot metadata, and theme toggle.
- Desktop 64px icon rail with tooltips and clear active state.
- Mobile compact top bar and safe-area bottom tabs.
- Content container maxes out at a readable desktop width and never clips at 390px.
- Page titles are compact and aligned with controls; no oversized display headings.

## Dashboard Pages

- Ringkasan: three KPI cards, primary player-demand chart, genre-distribution chart, and ranked table.
- Saturasi: concise page header, status legend, saturation chart, and detailed table.
- Viral: concise page header, count metric, horizontal momentum chart, and detailed table.
- All cards share one surface, radius, padding, title, and metadata pattern.
- Tables retain row headers, labels, keyboard usability, and horizontal overflow on narrow screens.
- Charts remain hidden from assistive technology when an equivalent table/list is present.

## Motion

- `motion` handles landing hero and section reveal with 180–500ms opacity/translate transitions.
- Lenis runs only on the landing page and is destroyed when not needed.
- Dashboard motion is limited to hover/focus and subtle panel entrance.
- `prefers-reduced-motion` disables Lenis, entrance motion, and ApexCharts animation.

## Data and Error Handling

- Keep `FastApiClient`, controllers, routes, and Blade variable names unchanged.
- Keep escaped table output and `@json` chart transport.
- Keep `registerChart()` as the only chart constructor.
- Full and partial API failures remain distinguishable from valid zero data.
- Existing unavailable/no-data/error wording remains intact.

## Accessibility

- One `h1` per page and real ordered `h2`/`h3` hierarchy.
- Visible focus states, 44px minimum touch targets, row headers, labelled tables, and chart data equivalents.
- Theme toggle has a stable accessible name.
- Decorative visuals are `aria-hidden`.
- Both themes target WCAG AA.

## Testing

- Preserve all PHPUnit route, content, fallback, and active-navigation assertions.
- Add assertions for the new product-led landing structure and dashboard shell markers.
- Keep the JavaScript reduced-motion tests.
- Run Node tests, Vite build, and the complete Laravel test suite.
- Add Playwright smoke coverage at 1440×1000 and 390×844 in light and dark modes for landing and all dashboard routes.

## Success Criteria

- Landing looks like a real analytics product, not a portfolio or poster.
- Dashboard density, spacing, typography, and surfaces feel consistent with premium SaaS products.
- Dark and light modes are independently polished.
- True 390px layout has no horizontal clipping and bottom navigation respects safe areas.
- All existing data behavior and automated tests continue to pass.
