# Roblox Signal Lab Landing — Design Specification

**Date:** 2026-07-19

**Status:** Approved for implementation planning

**Reference:** `C:\Users\VICTUS\Downloads\HNWiyIQa8AAOIWy.jpg`

**Approved mockup:** `.superpowers/brainstorm/20260719-103955/content/final-direction-v2.html`

## 1. Objective

Rebuild the Laravel landing page as a high-fidelity editorial research poster inspired by the supplied reference. The page must closely follow the reference's composition, scale, section rhythm, graphic density, and color relationships while retaining the Roblox Trends identity, Indonesian copy, live analytics data, and existing navigation.

The result should feel like a Roblox market-research studio rather than a conventional SaaS dashboard. The light theme is the primary visual identity; the existing dark theme remains supported as a complementary mode.

## 2. Selected Direction

The selected direction is **Roblox Signal Lab — high-fidelity editorial poster**.

It uses:

- A tall, edge-to-edge page composition with a narrow editorial canvas.
- Oversized, tightly tracked grotesk headlines.
- Selective italic serif words for contrast.
- Warm paper surfaces with subtle dot texture.
- Cobalt, coral, mint, magenta, amber, and pale-sky accents.
- Flat grids, thin rules, squared cards, and almost no shadow or rounding.
- Generative SVG/CSS graphics: flowing signal lanes, paper planes, dot fields, wave plots, fingerprint lines, arches, and concentric targets.
- The same broad section order and visual cadence as the reference, adapted to Roblox analytics content.

The implementation must not copy the reference brand, logo, company name, copy, or proprietary imagery. All graphics are recreated as local SVG/CSS motifs tied to Roblox market signals.

## 3. Scope

### In scope

- Rebuild `GET /` in `Frontend/resources/views/landing.blade.php`.
- Add landing-scoped styles in `Frontend/resources/css/app.css`.
- Add small presentational Blade components only where they reduce SVG or pattern duplication.
- Replace the current English-led landing copy with Indonesian copy.
- Preserve live snapshot and genre-ranking data.
- Preserve theme switching, API fallback states, semantic HTML, reduced-motion support, and responsive behavior.
- Update landing feature tests to describe the new page contract.
- Verify the page in light and dark themes at desktop and mobile widths.

### Out of scope

- Changes to FastAPI endpoints, analytics calculations, ingestion, or database schema.
- Restyling `/dashboard`, `/dashboard/saturasi`, or `/dashboard/viral`.
- New routes, authentication, forms, pricing, or marketing features.
- External illustration or image dependencies.
- Copying the reference's brand assets or literal editorial content.

## 4. Information Architecture

The landing page follows this sequence:

1. **Minimal header**
   - Landing-specific `RblxLab` mark linking to `/`, with the accessible label “Analisis Trend Roblox — beranda”. The shared dashboard wordmark remains unchanged.
   - Compact anchors for Ranking, Saturasi, Momentum, Tentang, and Catatan Data.
   - Primary CTA to `/dashboard`.
   - Accessible theme toggle remains available without dominating the header.

2. **Editorial hero**
   - Four-line headline: “Kami membaca bagaimana tren menjadi peluang.”
   - Short studio description and CTA.
   - Five colored signal lanes that curve upward into dotted nodes.
   - Warm-paper dot field behind the composition.

3. **Manifesto band**
   - Pale-sky full-width block.
   - Headline: “Data adalah sebuah praktik.” with an italic serif accent.
   - Four colored paper planes following dashed paths.
   - Supporting statement about curiosity, testing assumptions, and reading patterns.

4. **Research pillars**
   - Three ruled rows: `01 Ranking`, `02 Saturasi`, and `03 Momentum`.
   - Each row combines oversized type, a different colored dot field, explanation, and dashboard link.

5. **Selected signals**
   - Four squared poster cards named Atlas, Lensa, Jejak, and Muse.
   - Cards map to live snapshot count, genre count, leader/momentum context, and dashboard discovery.
   - Graphic motifs mirror the reference's density without copying its branded illustrations.

6. **Process strip**
   - Four stages: Temukan, Bersihkan, Uji, Tampilkan.
   - A continuous multicolor top rule and compact explanatory copy.

7. **Primary CTA band**
   - Full-width coral block.
   - Statement about decisions beginning with clear signals.
   - Large concentric target graphic using the full accent palette.
   - CTA to `/dashboard`.

8. **Catatan data**
   - Four compact cards derived from the top ranking genres or stable editorial fallback copy.
   - Each card has a distinct generative dot/stripe motif and a meaningful link.

9. **Closing statement**
   - “Bawa kami sebuah pertanyaan sulit.”
   - Supporting copy and dashboard CTA.
   - Large cobalt brand tile.

10. **Compact footer**
    - Brand, navigation groups, methodology/source attribution, and year.

## 5. Visual System

### Color palette

Light mode uses these primary targets:

| Role | Color |
| --- | --- |
| Warm paper | `#F4F0E6` |
| Near-black ink | `#080B19` |
| Cobalt | `#1747E8` |
| Coral | `#FF4B31` |
| Mint | `#5ACDA7` |
| Amber | `#F2A43A` |
| Magenta | `#ED55BD` |
| Pale sky | `#CFE8F3` |

Dark mode keeps the same accent relationships on near-black surfaces. Paper-colored text and lighter ruled lines replace the light-mode ink and borders. Coral, mint, amber, magenta, and cobalt must remain distinguishable and pass contrast checks wherever they carry text.

### Typography

- Continue using the locally available grotesk/system sans stack; do not add a runtime font CDN.
- Display headlines use fluid sizing, approximately `clamp(3.5rem, 10vw, 7.5rem)` depending on the section.
- Display line height targets `0.80–0.88`, with negative tracking around `-0.06em`.
- Georgia or the existing serif token is used only for one or two italic emphasis words.
- Eyebrows and action labels use compact uppercase text with wide tracking.
- Body text stays readable at conventional line height and never uses display-scale compression.

### Geometry and texture

- Section boundaries use thin, visible rules.
- Cards are squared or nearly squared with no decorative drop shadows.
- The landing canvas uses a subtle radial-dot paper texture.
- Graphics are intentionally clipped at section edges to reproduce the poster-like energy of the reference.
- All landing-specific CSS is scoped under `[data-page='landing']` to prevent dashboard regressions.

## 6. Component and File Architecture

The main composition remains server-rendered in `landing.blade.php`. Three decorative visual units become components under `resources/views/components/analytics/`:

- `signal-stream`: decorative five-lane hero SVG.
- `paper-planes`: decorative manifesto illustration.
- `target-rings`: decorative CTA graphic.

Dot fields, waves, fingerprints, and card arches remain landing-scoped CSS because they are single-use motifs.

These components accept presentation props only and remain `aria-hidden`. They do not access services, transform analytics, or own navigation.

Existing shared components remain in use where appropriate:

- `theme-toggle`
- `status-panel`
- BlatUI buttons and alerts when their markup fits the flat editorial treatment

The global BlatUI foundation and dashboard components must not be rewritten for this landing-only task.

## 7. Data Flow

The server-side flow remains unchanged:

`FastApiClient -> DashboardController::landing() -> landing.blade.php`

Available data:

- `$snapshot['game_count']`
- `$snapshot['snapshot_id']`
- `$snapshot['taken_at']`
- `$ranking['ranking']`
- Ranking item fields including `genreL1`, `game_count`, `avg_visits`, `avg_playing`, and `avg_rating`

Dynamic mappings:

- Atlas card: total game count.
- Lensa card: number of ranked genres.
- Jejak card: leading genre and its `game_count` value.
- Muse card: latest snapshot identifier with its timestamp as supporting text.
- Catatan data: up to four top genres.
- Ranking preview: top five genres rendered as a flat editorial horizontal plot through the existing `registerChart()` helper.

The accessible `data-ui='genre-ranking-list'` list remains the nonvisual representation of the chart. Blade output uses escaped `{{ }}` and chart data uses `@json`.

## 8. Error and Empty States

- The hero, manifesto, research explanation, navigation, CTA, closing, and footer always render.
- If the API is unavailable, the existing shared status panel explains that the analysis server is inactive.
- If no snapshot exists, the no-data guidance remains visible.
- If ranking is empty, the page does not index into the first item and shows the existing “Belum terbaca” message in the selected-signals area.
- Dynamic cards use meaningful editorial fallback labels rather than zeros presented as real market findings.
- JavaScript chart setup is skipped when no chart data exists.

## 9. Responsive Behavior

- Desktop/tablet preserves the narrow, tall poster canvas and reference-like proportions.
- The hero headline scales without horizontal overflow and keeps intentional four-line wrapping where space permits.
- At mobile widths, header links collapse while brand, CTA, and theme control remain reachable.
- Signal lanes are clipped within the hero rather than extending the page width.
- Manifesto copy stacks above the paper-plane field.
- Research rows change from four columns to a two-column hierarchy with graphics and descriptions aligned below the title.
- Selected-signal and note grids change from four columns to two, then one when necessary.
- Process becomes a two-column or vertical sequence.
- The CTA target moves below the copy.
- All interactive targets are at least 44 by 44 CSS pixels.

## 10. Motion and Accessibility

- Use semantic `header`, `nav`, `main`, `section`, `article`, and `footer` landmarks.
- Keep one `h1` and ordered section headings.
- Decorative SVGs and CSS motifs are `aria-hidden='true'` and cannot receive focus.
- Links have visible focus treatment and descriptive Indonesian labels.
- Color never replaces text labels for Ranking, Saturasi, or Momentum.
- The theme toggle retains its accessible label.
- The signal lanes use a slow horizontal drift and the paper planes use a subtle float. No scroll-triggered section reveal is added.
- `prefers-reduced-motion: reduce` removes nonessential animation and smooth scrolling.
- Text contrast targets WCAG AA in both themes.

## 11. Testing and Verification

### Automated

- Update `Frontend/tests/Feature/LandingTest.php` for the new Indonesian headline and section labels.
- Preserve assertions for `data-page='landing'`, the Dashboard CTA, live game count, genre output, API-down survival, empty ranking, and endpoint failure.
- Replace brittle assertions tied to exactly two ASCII fields with assertions for the new decorative/semantic contract.
- Preserve the screen-reader ranking list assertion.
- Run `php artisan test` from `Frontend`.
- Run `node --test tests/JavaScript/chart-preferences.test.mjs`.
- Run `npm run build`.

### Visual

- Compare the implemented page against the supplied reference and approved mockup.
- Verify desktop around 1440 px, tablet around 768 px, and mobile around 390 px.
- Verify light and dark themes.
- Verify theme switching updates the ranking visualization without reload.
- Verify API unavailable, no-data, empty-ranking, and normal live-data states.
- Verify no horizontal overflow and no text collision in the hero, research rows, cards, or CTA rings.

## 12. Expected Files Changed

- `Frontend/resources/views/landing.blade.php`
- `Frontend/resources/css/app.css`
- `Frontend/tests/Feature/LandingTest.php`
- `Frontend/resources/views/components/analytics/signal-stream.blade.php`
- `Frontend/resources/views/components/analytics/paper-planes.blade.php`
- `Frontend/resources/views/components/analytics/target-rings.blade.php`

No backend, controller, route, or dashboard-view change is expected.

## 13. Success Criteria

- At first glance, the landing page is recognizably close to the supplied reference in composition, rhythm, typography, density, and palette.
- The page reads entirely as Roblox Trends/RblxLab rather than as the reference brand.
- All primary marketing copy is Indonesian.
- Live snapshot and ranking information remain correct and meaningful.
- Dashboard navigation, theme switching, API fallback states, and accessible ranking content continue to work.
- The landing page is responsive, keyboard-accessible, reduced-motion aware, and isolated from dashboard styling.
- The Laravel test suite, JavaScript test, and Vite production build pass.
