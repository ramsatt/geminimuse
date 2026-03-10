# GeminiMuse — UX Design Plan

> Role: UX Designer
> Prepared: March 2026
> Stack: Angular 21 + Tailwind CSS 4 + Capacitor 8
> Scope: Mobile-first, responsive across all screen sizes

---

## Executive Summary

GeminiMuse is an AI image prompt gallery app with multilingual support (Tamil, Hindi, Telugu, Kannada, Malayalam, English). The current design is functional and clean but lacks a strong visual identity, hierarchy consistency, and a truly polished professional feel. This plan delivers a complete design system overhaul — dark-mode-ready, grid-adaptive, and conversion-optimized — without changing the Angular architecture.

---

## Current UX Audit

### Strengths
- Clean white card layout with subtle blur background effects
- Good use of Tailwind utility classes
- Virtual scroll implemented correctly
- Language pill selector is creative and intuitive
- Onboarding tour exists

### Issues Identified

| Screen | Issue | Severity |
|--------|-------|----------|
| Home | Two filter chip rows stacked (category + AI tool) consume too much header space on mobile | High |
| Home | Header has `pt-10` hard-coded — unsafe area not handled for notch devices | High |
| Home | Search bar filter icon (sliders) does nothing — dead UI | Medium |
| Home | "New This Week" horizontal strip is too small (24–28px cards) — hard to tap | High |
| Home | `animate-fade-in-up` used without `prefers-reduced-motion` guard | Low |
| Prompt Card | Hover-only interactions — entire card info hidden on touch devices | Critical |
| Prompt Card | Favorite button opacity-0 until hover — unusable on mobile | Critical |
| Prompt Details | Split-panel layout collapses well on md+ but `h-[65vh]` image panel feels cramped on tall phones | Medium |
| Prompt Details | Language pills on right side overlap image on small screens | High |
| Prompt Details | Copy button (yellow) is `h-16/h-20` — good on mobile, too large on desktop | Low |
| Prompt Details | "Try with" tool buttons are borderline too small on mobile (36px target) | Medium |
| Tour | Single card modal is good; animations could be richer | Low |
| Global | No dark mode support | Medium |
| Global | Color palette lacks a strong brand accent — gray/yellow inconsistency | Medium |
| Global | Typography scale inconsistent (mixes `text-[0.65rem]`, `text-[9px]`, `text-[10px]`) | Low |

---

## Design System

### 1. Color Palette

Replace the current ad-hoc palette with a structured token system:

```
Brand Tokens
├── --color-brand-primary:    #6C47FF   (Electric Violet — AI/tech feel)
├── --color-brand-accent:     #FFD60A   (Golden Yellow — kept from current CTA)
├── --color-brand-surface:    #F7F7FB   (Off-white background, replaces #F5F6F7 / #F0F2F5)
│
Light Mode
├── --color-bg:               #F7F7FB
├── --color-surface:          #FFFFFF
├── --color-surface-raised:   #FFFFFF (with shadow)
├── --color-text-primary:     #111111
├── --color-text-secondary:   #6B7280
├── --color-text-muted:       #9CA3AF
├── --color-border:           #E5E7EB
├── --color-chip-active-bg:   #111111
├── --color-chip-active-text: #FFFFFF
│
Dark Mode (future)
├── --color-bg:               #0D0D12
├── --color-surface:          #18181F
├── --color-surface-raised:   #1F1F2A
├── --color-text-primary:     #F0F0F5
├── --color-text-secondary:   #9CA3AF
├── --color-border:           #2A2A38
├── --color-chip-active-bg:   #6C47FF
├── --color-chip-active-text: #FFFFFF
```

### 2. Typography Scale

Use a single font stack — **Inter** (already web-standard, matches current sans-serif approach):

```
Display:   font-size: 2.5rem (40px) / font-weight: 800 / letter-spacing: -0.03em
H1:        font-size: 2rem   (32px) / font-weight: 700 / letter-spacing: -0.02em
H2:        font-size: 1.5rem (24px) / font-weight: 700 / letter-spacing: -0.01em
H3:        font-size: 1.125rem      / font-weight: 600
Body-L:    font-size: 1rem   (16px) / font-weight: 400 / line-height: 1.6
Body-S:    font-size: 0.875rem      / font-weight: 400 / line-height: 1.5
Caption:   font-size: 0.75rem       / font-weight: 500 / letter-spacing: 0.04em
Label:     font-size: 0.6875rem     / font-weight: 700 / letter-spacing: 0.08em / text-transform: uppercase
```

> **Rule:** Never use arbitrary sizes below `text-xs` (0.75rem / 12px). Replace `text-[9px]`, `text-[0.65rem]` etc. with `text-xs` or rethink the component.

### 3. Spacing & Radius

```
Spacing base: 4px
Grid:         8 / 12 / 16 / 24 / 32 / 48 / 64px

Radius tokens:
  --radius-sm:   8px    (inline chips, badges)
  --radius-md:   12px   (buttons, input fields)
  --radius-lg:   20px   (cards)
  --radius-xl:   28px   (modals, panels)
  --radius-full: 9999px (pills)
```

### 4. Elevation / Shadow

```
Level 0: no shadow (flat, bg-surface elements)
Level 1: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)   — cards resting
Level 2: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.05)  — cards hover, modals
Level 3: 0 12px 32px rgba(0,0,0,0.12)                               — floating elements, CTAs
```

### 5. Motion Tokens

```
Duration: fast=150ms, normal=250ms, slow=400ms
Easing:   ease-out for entrances, ease-in for exits, spring for interactive (cubic-bezier(0.34,1.56,0.64,1))
Rule:     All animations MUST respect @media (prefers-reduced-motion: reduce)
```

---

## Screen-by-Screen Redesign

---

### Screen 1 — Home Page

#### Current Pain Points
- Header stack: title + search + 2 filter rows = ~200px on mobile before any content
- "New This Week" cards too small
- Cards show info only on hover

#### Redesign Spec

**Layout Structure (mobile-first):**
```
┌─────────────────────────────────────────┐
│  Safe-area-top                          │  ← env(safe-area-inset-top)
│  ┌───────────────────────────────────┐  │
│  │  [Logo]   GeminiMuse   [♥] [👤]  │  │  ← Compact top bar, 56px
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  🔍 Search prompts...      [≡]   │  │  ← Persistent search, 52px
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  All  Portrait  Cinematic  ...→  │  │  ← Single scrollable chip row, 44px
│  └───────────────────────────────────┘  │
│                                         │
│  ╔═══ New This Week (if any) ═════════╗ │
│  ║  [img 80px] [img 80px] [img 80px] ║ │  ← Taller cards, 80×107px (3:4)
│  ╚════════════════════════════════════╝ │
│                                         │
│  ┌─────────┐  ┌─────────┐              │
│  │         │  │         │              │  ← Main grid
│  │  Card   │  │  Card   │              │  ← 2-col mobile, 3-col tablet, 4-col desktop
│  │         │  │         │              │
│  └─────────┘  └─────────┘              │
└─────────────────────────────────────────┘
```

**Key Changes:**

1. **Compact header bar (56px)**
   - App icon (32px) + "GeminiMuse" wordmark left-aligned
   - Favorites heart + profile avatar right-aligned
   - No tagline "Welcome to the gallery" — wastes space

2. **Merged filter UX — Bottom Sheet instead of stacked rows**
   - Keep ONE chip row: category only (scrollable)
   - The `[≡]` filter icon opens a **bottom sheet** with:
     - AI Tool filter (radio chips)
     - Difficulty filter
     - Sort: Newest / Most Copied / Random
   - Bottom sheet closes with swipe-down gesture
   - Active filters show a dot badge on the `[≡]` icon

3. **"New This Week" strip — upgraded**
   - Cards 80×107px (3:4 ratio) with `NEW` badge visible always (not hover-only)
   - Strip label as a styled section header with gradient shimmer
   - Horizontal scroll with snap points

4. **Prompt Cards — Always-visible bottom info strip**
   - Remove hover-only mechanism
   - Always show: category badge (top-left) + favorite button (top-right)
   - Bottom: subtle gradient overlay with truncated prompt text (2 lines, always visible)
   - On tap: navigates to detail
   - Long-press: quick action sheet (Copy, Share, Favorite)

5. **Grid Responsive Breakpoints:**
   ```
   < 480px   → 2 columns, gap-3
   480–767px → 2 columns, gap-4
   768–1023px → 3 columns, gap-5
   1024–1439px → 4 columns, gap-6
   1440px+   → 5 columns, gap-6, max-w-screen-2xl centered
   ```

6. **Search UX**
   - On focus: keyboard-aware viewport adjustment (Capacitor keyboard plugin)
   - Show recent searches as chips below search bar
   - Clear button (×) appears when there is input

7. **Scroll-to-top FAB**
   - Replace the "Scroll for more" indicator with a scroll-to-top FAB (bottom-right, 48px)
   - Appears after scrolling past 2 viewport heights
   - Uses brand violet color

---

### Screen 2 — Prompt Details Page

#### Current Pain Points
- Language pills on the right overlap image on small screens
- `h-[65vh]` image panel is cramped on phones with tall headers
- CTA button area and related prompts compete for bottom space

#### Redesign Spec

**Mobile Layout (< 768px) — Scrollable single column:**
```
┌─────────────────────────────────────────┐
│  [←]    Gemini Muse        [♥][↗][⎘]  │  ← Nav bar (56px)
│─────────────────────────────────────────│
│                                         │
│  ┌─────────────────────────────────┐   │
│  │                                 │   │
│  │         AI Image                │   │  ← Full-width image, aspect-[4/5]
│  │         (aspect ratio locked)   │   │
│  │                                 │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─ Language Pills (horizontal) ──────┐ │  ← MOVED: horizontal row below image
│  │  [EN] [தமிழ்] [हिंदी] [తెలుగు] ... │ │
│  └─────────────────────────────────────┘ │
│                                         │
│  ╔════════════════════════════════════╗ │
│  ║  PORTRAIT SERIES          #ID 042 ║ │  ← Category + ID row
│  ║  Prompt Details                   ║ │
│  ║  ─────────────────────────────    ║ │
│  ║  Prompt text shown here...        ║ │  ← Full visible prompt text
│  ║  in the selected language...      ║ │
│  ╚════════════════════════════════════╝ │
│                                         │
│  [ 3 free copies left ]                 │  ← Inline status, not badge
│                                         │
│  ┌─────────────────────────────────┐   │
│  │      ⎘  COPY PROMPT            │   │  ← Primary CTA, full width, 56px
│  └─────────────────────────────────┘   │
│                                         │
│  Try with: [MJ] [DALL-E] [Gemini]      │  ← 44px min tap target
│                                         │
│  ── Similar Prompts ───────────────    │
│  [card] [card] [card] →               │
│─────────────────────────────────────────│
│  safe-area-bottom                       │
└─────────────────────────────────────────┘
```

**Desktop Layout (≥ 768px) — Two-column panel:**
```
┌────────────────────────┬────────────────────────┐
│                        │  [←]  GeminiMuse  [♥↗⎘] │
│                        │─────────────────────────│
│   Full-height          │  PORTRAIT SERIES  #042  │
│   AI Image             │  Prompt Details         │
│   (object-contain)     │─────────────────────────│
│                        │  [EN][TM][HI][TE][KN]   │ ← Horizontal pills
│                        │─────────────────────────│
│                        │  Prompt text area       │
│                        │  (scrollable)           │
│                        │                         │
│                        │  [ 3 copies left ]      │
│─────────────────────── │  [  COPY PROMPT  ]      │
│   Language Pills       │                         │
│   (vertical, side)     │  Try with: [MJ][DALL-E] │
│   visible on desktop   │─────────────────────────│
│   only                 │  Similar Prompts        │
│                        │  [card][card][card]     │
└────────────────────────┴────────────────────────┘
```

**Key Changes:**

1. **Language selector — Adaptive layout**
   - Mobile: Horizontal scrollable pill row below image (no overlap)
   - Desktop: Vertical pill column on left panel side
   - Active state: filled brand-violet instead of black (more distinctive)
   - Use language name abbreviations (`EN`, `TM`, `HI`, `TE`, `KN`, `ML`) instead of flag emoji for cleaner look

2. **Image panel — Aspect ratio controlled**
   - Mobile: `aspect-[4/5]` with `object-contain` and light gray bg — no stretching
   - Desktop: Full height panel with blurred background version behind main image (depth effect)
   - Image tap → full-screen modal with pinch-zoom

3. **Copy button — Refined**
   - Height: 56px consistent across mobile and desktop
   - Color: `#FFD60A` (brand accent, consistent)
   - On success: button morphs to green checkmark for 2 seconds then resets
   - Disabled state: grayed out with lock icon when copies exhausted

4. **Free copies indicator**
   - Replace badge with an inline progress-bar style indicator:
     `●●●○ 3 of 3 copies remaining`
   - Feels less alarming, more informational

5. **"Try With" buttons — Larger targets**
   - Min height 44px, include AI tool icon/logo
   - Rounded pill style with subtle colored border per tool (MJ=purple, DALL-E=green, Gemini=blue)

---

### Screen 3 — Onboarding Tour

#### Redesign Spec

**Replace static card with immersive slides:**

```
Step 1: Welcome
  - Full-screen gradient background (#6C47FF → #9C6FFF)
  - Large app icon (120px) with glow ring
  - "Welcome to GeminiMuse" — white, Display size
  - Subtitle in white/70
  - Dots indicator at bottom

Step 2: Browse & Discover
  - Show actual app screenshot / mockup on right side
  - Split: left = icon + text, right = preview

Step 3: Multilingual
  - Language pills animation demonstration
  - Cycle through languages with live text change

Step 4: Copy & Create
  - Copy button animation
  - Short celebration micro-animation

Step 5: Get Started
  - CTA "Start Exploring" in brand accent (yellow)
  - Skip entirely if returning user (persist flag in localStorage)
```

---

### Screen 4 — Filter Bottom Sheet (New)

A new UI surface replacing the second chip row on home:

```
┌─────────────────────────────────────────┐
│           ▬ drag handle                 │
│  Filters                        [Reset] │
│─────────────────────────────────────────│
│  AI Tool                                │
│  [● All] [Midjourney] [DALL-E 3]        │
│  [Stable Diffusion] [Firefly]           │
│─────────────────────────────────────────│
│  Difficulty                             │
│  [All] [Beginner] [Intermediate]        │
│  [Advanced]                             │
│─────────────────────────────────────────│
│  Sort By                                │
│  [● Newest] [Most Copied] [Random]      │
│─────────────────────────────────────────│
│                                         │
│  [ Apply Filters ]   ← primary CTA      │
│─────────────────────────────────────────│
│  safe-area-bottom                       │
└─────────────────────────────────────────┘
```

---

### Screen 5 — Reward Ad Modal

#### Current Issues
- Modal is well-designed but the icon (gift) is confusing — watching an ad ≠ gift
- "Maybe Later" text is too small to be a real escape

#### Redesign

```
┌─────────────────────────────────────────┐
│  ×                                      │  ← visible close (top-right, 44px)
│                                         │
│       [▶ Play Ad icon — 80px]           │  ← Clearer icon
│                                         │
│    Unlock 10 More Copies                │  ← H2
│    Watch a short ad to continue         │  ← Body, softer tone
│                                         │
│    ╔══════════════════════════════╗     │
│    ║   Watch Ad (30 sec)         ║     │  ← Primary CTA
│    ╚══════════════════════════════╝     │
│                                         │
│    ─────── or ───────                   │
│                                         │
│    Upgrade to Pro — No more ads         │  ← Upsell link, secondary
│                                         │
└─────────────────────────────────────────┘
```

---

## Responsive Design Breakpoints

| Breakpoint | Name | Width | Layout Change |
|-----------|------|-------|---------------|
| `xs` | Mobile S | < 375px | 2-col grid, compact header |
| `sm` | Mobile L | 375–767px | 2-col grid, normal spacing |
| `md` | Tablet | 768–1023px | 3-col grid, split-panel on details |
| `lg` | Desktop S | 1024–1439px | 4-col grid, sidebar nav (future) |
| `xl` | Desktop L | 1440px+ | 5-col grid, max-width container |

### Safe Area Handling
```css
/* Replace hard-coded pt-10 with: */
padding-top: env(safe-area-inset-top, 16px);
padding-bottom: env(safe-area-inset-bottom, 16px);
```

### Keyboard Avoidance (Mobile Web + Capacitor)
- Wrap main content in `height: 100dvh` (dynamic viewport height)
- When search is focused, scroll content up so search stays visible
- Use `@capacitor/keyboard` plugin events to adjust layout

---

## Component Redesign Priority List

Ordered by user impact × implementation effort:

| Priority | Component | Change | Impact |
|----------|-----------|--------|--------|
| P0 | `prompt-card` | Always-visible info strip (remove hover-only) | Critical — mobile UX broken |
| P0 | `home` header | Merge filters → bottom sheet, compact header | Critical — screen real estate |
| P0 | `prompt-details` | Move language pills below image on mobile | Critical — overlap bug |
| P1 | Design tokens | CSS custom properties + Inter font | High — consistency |
| P1 | `home` grid | Responsive column count + safe area | High |
| P1 | `prompt-card` | Favorite button always visible (top-right) | High |
| P2 | `prompt-details` | Aspect-ratio locked image, progress bar copies | Medium |
| P2 | `tour` | Immersive gradient slides | Medium |
| P2 | Filter sheet | New bottom sheet component | Medium |
| P3 | Dark mode | CSS variable switch via `prefers-color-scheme` | Low (future) |
| P3 | Scroll FAB | Replace "scroll for more" hint | Low |
| P3 | Animations | Add `prefers-reduced-motion` guards | Low |

---

## Accessibility Checklist

- [ ] All interactive elements ≥ 44×44px touch target
- [ ] Color contrast: text on bg ≥ 4.5:1 (WCAG AA)
- [ ] Focus rings visible on all focusable elements (`focus-visible` not `focus`)
- [ ] All images have meaningful `alt` text (not just "Prompt 42")
- [ ] Modals trap focus (tour, reward modal, bottom sheet)
- [ ] Language pills have `aria-label="Select language: Tamil"` etc.
- [ ] `aria-live` region for copy success/error feedback
- [ ] `role="status"` on loading spinners
- [ ] Screen reader skip link at page top

---

## Implementation Order (Phased)

### Phase A — Foundation (Week 1)
1. Add Inter font via Google Fonts in `index.html`
2. Define CSS custom properties (color tokens, radius, shadow) in `app.css`
3. Add `env(safe-area-inset-*)` padding to global layout
4. Replace all `text-[Xpx]` arbitrary sizes with standard Tailwind classes

### Phase B — Card & Grid (Week 2)
5. Redesign `prompt-card` — always-visible bottom strip + visible favorite button
6. Update home grid responsive column count
7. Upgrade "New This Week" strip cards to 80px width with snap scroll

### Phase C — Navigation & Filters (Week 3)
8. Compact home header (56px)
9. Create `filter-sheet` bottom sheet component
10. Move AI tool + difficulty filters into bottom sheet
11. Add filter badge dot on filter icon

### Phase D — Detail Page (Week 4)
12. Horizontal language pills on mobile
13. Aspect-ratio locked image panel
14. Progress-bar copy counter
15. Larger "Try With" buttons (44px min)
16. Scroll-to-top FAB

### Phase E — Onboarding & Modals (Week 5)
17. Redesign tour with gradient slides
18. Refine reward ad modal layout
19. Add `prefers-reduced-motion` guards to all animations

### Phase F — Dark Mode (Week 6, Optional)
20. CSS variable dark theme switch
21. Test all screens in dark mode
22. Add system preference detection

---

## Design Assets Needed

| Asset | Format | Size | Purpose |
|-------|--------|------|---------|
| Inter font | WOFF2 | ~50KB | Body typography |
| App icon (high-res) | PNG | 512×512 | Tour + splash |
| Onboarding illustrations (4) | SVG | < 20KB each | Tour steps |
| "Empty favorites" illustration | SVG | < 10KB | Empty state |
| Language flag icons (optional) | SVG sprite | < 15KB | Language pills |
| Loading skeleton SVG | Inline SVG | — | Card skeletons |

---

## Metrics to Track Post-Redesign

| Metric | Expected Change | Tracking |
|--------|----------------|----------|
| Prompt card tap-through rate | +30% (always-visible info) | Firebase Events |
| Copy button conversion | +15% (better CTA hierarchy) | Firebase Events |
| Session length | +20% (better content discovery) | Firebase Analytics |
| Filter usage rate | +40% (accessible bottom sheet) | Firebase Events |
| Ad reward watch rate | +10% (clearer value prop) | AdMob + Firebase |
| Onboarding completion | +25% (engaging slides) | Firebase Events |

---

*This document should be used alongside [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) for implementation. Each Phase here maps to a sprint that can run in parallel with backend development phases.*
