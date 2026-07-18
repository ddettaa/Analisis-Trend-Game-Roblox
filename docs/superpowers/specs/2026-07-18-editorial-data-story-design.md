# Editorial Data Story Frontend Restyle — Design Specification

**Date:** 2026-07-18  
**Status:** Approved for implementation planning  
**Reference:** `C:\laragon\www\ddettaa`  
**Selected direction:** Editorial Data Story (hybrid)

## 1. Objective

Restyle the entire Laravel frontend for Analisis Trend Roblox with the visual character of ddettaa while preserving the current analytics functionality.

The landing page will be expressive and editorial: oversized typography, bold full-width color sections, dynamic ASCII texture, strong blue/red theme identities, and restrained motion. The dashboard will carry the same identity while remaining compact, scannable, and practical for data analysis.

BlatUI remains the component foundation. The restyle must use the existing published BlatUI components and tokens rather than replacing them with another UI library.

## 2. Scope

### In scope

- Restyle `/`, `/dashboard`, `/dashboard/saturasi`, and `/dashboard/viral`.
- Rebuild the shared dashboard layout and responsive navigation.
- Add reusable Blade presentation components where they reduce duplication.
- Extend the current BlatUI theme tokens with project-specific editorial tokens.
- Add lightweight Alpine/CSS motion inspired by ddettaa.
- Restyle all ApexCharts while keeping `registerChart()` and theme awareness.
- Preserve API fallback states and current server-rendered data flow.
- Update and extend frontend tests for the new shared structure.

### Out of scope

- Backend or FastAPI changes.
- New API endpoints or controller data contracts.
- React, Next.js, Vue, or another frontend framework.
- Copying personal portfolio content, project assets, or brand identity from ddettaa.
- Changing analytics calculations or table data.
- Adding authentication, filtering, export, or other product features.

## 3. Reference Characteristics

The design borrows the following characteristics from the local ddettaa source:

- Host Grotesk-like bold typography and very large editorial headlines.
- Minimal navigation with a compact wordmark.
- Full-width electric blue sections in light mode and signal red sections in dark mode.
- Animated ASCII texture used as atmosphere, not decoration on every surface.
- Strong horizontal rules, restrained rounding, and clear section boundaries.
- Theme transitions that feel like switching the visual identity of the same content.
- Simple, high-impact interaction rather than dense ornamental animation.

The Roblox frontend adapts these traits to analytics. Data legibility takes priority inside dashboard workspaces.

## 4. Visual System

### Color modes

- **Light:** warm off-white background, near-black foreground, electric blue accent.
- **Dark:** near-black background, warm white foreground, signal red accent.
- Analytics status colors retain semantic meaning and are not recolored purely for theme identity.
- Charts derive their primary series colors, labels, grids, and tooltips from the active mode.

Theme changes affect presentation only. Labels, metrics, rankings, and narrative text stay identical in both modes.

### Typography

- Use a locally bundled or Vite-loaded grotesk sans family; avoid runtime dependency on a remote font service.
- Display headlines use tight tracking and fluid sizes via `clamp()`.
- UI labels use small uppercase text with wider tracking.
- Data values use tabular numerals where supported.
- Body copy remains at a conventional readable size and line height.

### Shape and spacing

- Prefer strong section boundaries, thin rules, and restrained radii.
- BlatUI cards retain accessible structure but receive editorial top accents and flatter shadows.
- Use a consistent responsive spacing scale, with generous landing sections and tighter dashboard gaps.
- Tables remain information-dense and horizontally scrollable on narrow screens.

### Motion

- Landing hero, metric counters, and signal band may animate on entry.
- ASCII background uses lightweight CSS/Alpine animation and does not require a canvas render loop.
- Dashboard motion is limited to hover/focus feedback, navigation transitions, and chart rendering.
- `prefers-reduced-motion: reduce` disables non-essential movement and smooth scrolling.

## 5. Information Architecture

### Landing page

1. Minimal brand header with Dashboard CTA and theme toggle.
2. Full-height editorial hero: “Decode what Roblox plays.”
3. Full-width signal band with live snapshot statement and animated ASCII texture.
4. Live metric strip for game count, genre count, and leading genre.
5. Editorial insight section explaining ranking, saturation, and viral discovery.
6. Top-five genre chart preview.
7. Final CTA and compact footer.

When data is unavailable, sections that require live data collapse into a BlatUI status panel. The hero, product explanation, navigation, and Dashboard CTA remain visible.

### Dashboard shell

- Desktop: compact top bar plus slim left navigation rail.
- Mobile: compact sticky header plus bottom navigation with the same three destinations.
- Navigation items: Ringkasan, Saturasi, Viral Muda.
- Theme toggle remains available on every page.
- Snapshot metadata and API status appear in a consistent workspace header/status region.

### Dashboard pages

- **Ringkasan:** editorial title, three core metrics, genre composition chart, active-player chart, ranking table.
- **Saturasi:** editorial title, explanatory context, saturation chart, status legend, detail table with BlatUI badges.
- **Viral Muda:** editorial title, definition of the 90-day window, horizontal growth chart, game table.

## 6. Component Architecture

The implementation will continue using published components in `resources/views/components/ui/` for buttons, cards, badges, alerts, separators, and tables.

Small project-specific Blade components may be added under `resources/views/components/analytics/`:

- `brand-mark`: shared wordmark and compact R/T mark.
- `theme-toggle`: accessible theme control using the current Alpine theme store.
- `dashboard-nav`: desktop rail and mobile bottom navigation sourced from one item list.
- `page-heading`: eyebrow, title, and optional description.
- `metric-card`: label, value, optional annotation, and accent treatment.
- `ascii-field`: decorative, `aria-hidden` signal texture with reduced-motion support.
- `status-panel`: wraps the existing API state messages in the correct BlatUI alert structure.

These components accept presentation data only. They do not call services, transform analytics, or own page routing.

## 7. Data Flow and JavaScript

The current flow remains unchanged:

`FastApiClient -> DashboardController -> Blade view -> escaped table output / @json chart data -> registerChart()`

- Controllers keep their existing methods and view variables.
- Blade table values remain escaped through `{{ }}`.
- Charts are created only through `window.registerChart(el, options)`.
- The ApexCharts CDN remains absent.
- Alpine manages theme state, navigation behavior, metric count-up, and optional reveal motion.
- Theme changes continue to update already-rendered charts through the current chart registry.

## 8. Responsive Behavior

- Landing typography and section spacing scale fluidly from mobile to large desktop.
- Oversized headlines must wrap intentionally without horizontal overflow.
- The dashboard rail is hidden below the desktop breakpoint and replaced by bottom navigation.
- Main content adds bottom padding on mobile so the bottom navigation never covers tables or charts.
- Chart heights and horizontal/vertical orientations adapt where needed for readable labels.
- Data tables sit inside overflow containers and retain visible headers.
- Interactive targets are at least 44 by 44 pixels on touch devices.

## 9. Accessibility

- Semantic landmarks: header, nav, main, section, and footer.
- One primary `h1` per page with ordered subheadings.
- Visible focus rings using BlatUI theme tokens.
- Theme control has an accessible label and state text.
- Decorative ASCII output is hidden from assistive technology.
- Color is never the only saturation-status indicator; text badges remain present.
- Light and dark palettes target WCAG AA contrast for normal text.
- Reduced-motion preferences are respected.

## 10. Error and Empty States

Existing status meanings and user-facing guidance remain intact:

- `unavailable`: explain that the analysis server is inactive and retain the uvicorn command.
- `no_data`: explain that ingestion must run first.
- `error`: direct the operator to the uvicorn logs.

Dashboard states use the shared BlatUI status panel. Landing states use a friendlier compact version while retaining hero and navigation. Missing or empty ranking arrays must not cause indexing or chart errors.

## 11. Testing and Verification

### Automated

- Preserve all current route and content assertions.
- Preserve the landing API-down test.
- Add assertions for the shared brand/navigation structure.
- Add assertions that each dashboard page exposes its active navigation destination.
- Add coverage for empty ranking data on the landing page if it is not already covered.
- Run `npm run build` and the full `php artisan test` suite.

### Visual and interaction

- Verify landing and all three dashboard pages in light and dark modes.
- Verify desktop, tablet, and mobile widths.
- Verify sticky/bottom navigation does not cover content.
- Verify charts update colors after theme toggle without reloading.
- Verify reduced-motion behavior.
- Verify API unavailable/no-data/error states remain readable.
- Verify no ApexCharts CDN request or direct `new ApexCharts()` call exists in views.

## 12. Success Criteria

- The landing page is immediately recognizable as inspired by ddettaa’s bold editorial style.
- The dashboard shares the same typography, color identity, lines, and motion without sacrificing data density.
- BlatUI remains visibly and technically central to the component system.
- All existing functionality, routes, data contracts, and fallback behavior continue to work.
- The experience is responsive, accessible, theme-aware, and passes the complete frontend build and test suite.
