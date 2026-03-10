# GeminiMuse — Development Plan

> Role: System Architect & App Specialist
> Prepared: March 2026
> Stack: Angular 21 + Capacitor 8 + Tailwind CSS 4 + AdMob

---

## Architecture Overview

```
Current Stack (Keep as-is)
├── Frontend: Angular 21 (Standalone Components + Signals)
├── Mobile: Capacitor 8 (Android + iOS)
├── Styling: Tailwind CSS 4
├── Monetization: AdMob (Banner + Interstitial + Reward)
└── Data: Static JSON (local assets)

New Additions Required
├── Backend: Supabase (Auth + DB + Storage + Realtime)
├── Notifications: Firebase Cloud Messaging (FCM) via Capacitor
├── Payments: Razorpay (Indian payments, Pro tier)
├── Analytics: Firebase Analytics
└── Sharing: Capacitor Share API + Canvas API (share cards)
```

---

## Phase 0 — Foundation (Week 1–2)

> Goal: Clean the codebase and set up infrastructure before building new features.

### 0.1 Brand & App Config Update

**Files to change:**
- `capacitor.config.ts` — update `appName`
- `src/index.html` — update `<title>` and meta description
- `android/app/src/main/res/values/strings.xml` — update app name
- `ios/App/App/Info.plist` — update `CFBundleDisplayName`

**Add Open Graph & SEO meta tags to `index.html`:**
```html
<meta property="og:title" content="MuseAI — AI Prompt Gallery in Your Language" />
<meta property="og:description" content="Discover 500+ AI image prompts in Tamil, Hindi, Telugu, Kannada, Malayalam and English." />
<meta property="og:image" content="https://codingtamilan.in/gemini-muse/assets/og-image.jpg" />
<meta name="twitter:card" content="summary_large_image" />
```

### 0.2 Project Structure Refactor

Reorganize for scalability before adding new features:

```
src/app/
├── core/
│   ├── services/
│   │   ├── data.service.ts          (rename from data.ts)
│   │   ├── admob.service.ts         (move from admob/)
│   │   ├── auth.service.ts          (NEW)
│   │   ├── notification.service.ts  (NEW)
│   │   └── analytics.service.ts     (NEW)
│   ├── guards/
│   │   └── auth.guard.ts            (NEW)
│   └── models/
│       ├── prompt.model.ts          (NEW — extract GeminiRef interface)
│       └── user.model.ts            (NEW)
├── pages/
│   ├── home/
│   ├── prompt-details/
│   ├── privacy-policy/
│   ├── profile/                     (NEW)
│   └── pro-upgrade/                 (NEW)
├── components/
│   ├── tour/
│   ├── share-card/                  (NEW)
│   ├── prompt-card/                 (NEW — extract from home)
│   └── language-selector/           (NEW — extract from prompt-details)
└── shared/
    └── pipes/
        └── language.pipe.ts         (NEW)
```

### 0.3 Data Model Extension

Extend `GeminiRef` interface in `src/app/core/models/prompt.model.ts`:

```typescript
export interface Prompt {
  id: number;
  filename: string;
  url: string;
  prompt: string;
  prompt_tn?: string;
  prompt_ml?: string;
  prompt_te?: string;
  prompt_kn?: string;
  prompt_hi?: string;
  source?: string;

  // NEW FIELDS
  category: PromptCategory;
  difficulty: 'beginner' | 'intermediate' | 'advanced';
  ai_tool: AiTool[];
  is_new: boolean;           // added in last 7 days
  is_premium: boolean;       // Pro-only prompts
  copy_count: number;        // social proof counter
  tags: string[];
  pack_id?: string;          // seasonal pack grouping
  added_date: string;        // ISO date string
}

export type PromptCategory =
  | 'all' | 'portrait' | 'cinematic' | 'fantasy'
  | 'anime' | 'street' | 'nature' | 'sci-fi'
  | 'architecture' | 'food' | 'wildlife' | 'abstract'
  | 'festive' | 'fashion' | 'macro';

export type AiTool = 'midjourney' | 'dall-e-3' | 'stable-diffusion' | 'firefly' | 'ideogram';
```

---

## Phase 1 — Core Feature Additions (Week 3–6)

### 1.1 Share Card Feature

**Complexity:** Medium | **Impact:** High (viral growth)

**Implementation:**

Create `src/app/components/share-card/share-card.component.ts`:
- Use **HTML Canvas API** to render a branded image card
- Card layout: AI image + prompt text + app logo watermark + language label
- Export as PNG blob

```typescript
// share-card.service.ts
generateShareCard(prompt: Prompt, language: Language): Promise<Blob> {
  const canvas = document.createElement('canvas');
  // Draw: background gradient → AI image → prompt text overlay → logo
  // Return canvas.toBlob()
}
```

Use **`@capacitor/share`** plugin to trigger native share sheet:
```typescript
await Share.share({
  title: 'Check this AI prompt',
  text: prompt.prompt,
  url: `https://codingtamilan.in/gemini-muse/#/prompt/${prompt.id}`,
  files: [shareImageUri],  // native only
  dialogTitle: 'Share this prompt',
});
```

**Dependencies to add:**
```bash
npm install @capacitor/share
npx cap sync
```

---

### 1.2 Favorites / Bookmarks

**Complexity:** Low | **Impact:** High (retention)

**Implementation:** Local-first with optional cloud sync later.

```typescript
// favorites.service.ts
export class FavoritesService {
  private favorites = signal<number[]>(
    JSON.parse(localStorage.getItem('favorites') ?? '[]')
  );

  toggle(id: number) {
    const current = this.favorites();
    const updated = current.includes(id)
      ? current.filter(f => f !== id)
      : [...current, id];
    localStorage.setItem('favorites', JSON.stringify(updated));
    this.favorites.set(updated);
  }

  isFavorite(id: number) {
    return computed(() => this.favorites().includes(id));
  }
}
```

**UI Changes:**
- Heart icon on each prompt card (home grid)
- Heart icon on prompt-details header
- New "Favorites" tab filter chip on home page
- Empty state illustration when no favorites saved

---

### 1.3 Copy Count Badge (Social Proof)

**Complexity:** Low | **Impact:** Medium

- Display on prompt-details: *"Copied 1.2K times"*
- Store counts in Supabase (see Phase 2)
- For Phase 1: increment a local counter, sync to backend when available
- Format: `< 1000 = exact`, `≥ 1000 = "1.2K"`, `≥ 1M = "1.2M"`

---

### 1.4 "New This Week" Section

**Complexity:** Low | **Impact:** Medium

- Add `is_new: true` flag to prompts added in the last 7 days in `prompts.json`
- Add a horizontal scroll row at top of home page: *"New This Week"*
- Separate from the main grid, always visible regardless of active filter
- Auto-hides if no new prompts exist

---

### 1.5 AI Tool Filter

**Complexity:** Low | **Impact:** Medium

- Add filter chips row below existing category chips: `All Tools | Midjourney | DALL-E 3 | Stable Diffusion | Firefly`
- Filter is independent of category (combinable)
- Store selected tool in Angular signal, filter `promptList` reactively

---

### 1.6 "Try With" Deep Links

**Complexity:** Low | **Impact:** Medium

On prompt-details page, add action buttons:
```
[Try on Midjourney]  [Try on ChatGPT]  [Try on Gemini]
```

- Midjourney: Opens Discord deep link with prompt pre-filled
- ChatGPT: `https://chat.openai.com/?q=Generate+image:+{prompt}`
- Gemini: `https://gemini.google.com/app?q={prompt}`
- Use `@capacitor/browser` to open in-app browser

---

## Phase 2 — Backend Integration (Week 7–10)

> Goal: Add persistence, user accounts, and server-driven content.

### 2.1 Supabase Setup

**Why Supabase:** Free tier is generous, has realtime, auth, storage, and works well with Angular via `@supabase/supabase-js`.

```bash
npm install @supabase/supabase-js
```

**Database Schema:**

```sql
-- Users (managed by Supabase Auth)
-- No manual users table needed

-- Prompts (server-side copy, synced from JSON)
CREATE TABLE prompts (
  id SERIAL PRIMARY KEY,
  filename TEXT,
  url TEXT,
  prompt TEXT,
  prompt_tn TEXT,
  prompt_ml TEXT,
  prompt_te TEXT,
  prompt_kn TEXT,
  prompt_hi TEXT,
  category TEXT,
  difficulty TEXT,
  ai_tools TEXT[],
  is_premium BOOLEAN DEFAULT false,
  is_new BOOLEAN DEFAULT false,
  copy_count INTEGER DEFAULT 0,
  tags TEXT[],
  pack_id TEXT,
  added_date TIMESTAMPTZ DEFAULT NOW()
);

-- User Favorites
CREATE TABLE favorites (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  prompt_id INTEGER REFERENCES prompts(id),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE(user_id, prompt_id)
);

-- Copy Events (for count tracking)
CREATE TABLE copy_events (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  prompt_id INTEGER REFERENCES prompts(id),
  user_id UUID REFERENCES auth.users(id) NULL,  -- nullable for anonymous
  language TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Pro Subscriptions
CREATE TABLE subscriptions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  plan TEXT DEFAULT 'pro',
  status TEXT DEFAULT 'active',   -- active | expired | cancelled
  razorpay_subscription_id TEXT,
  started_at TIMESTAMPTZ DEFAULT NOW(),
  expires_at TIMESTAMPTZ
);
```

**Row Level Security (RLS):**
```sql
-- Users can only read/write their own favorites
ALTER TABLE favorites ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Own favorites" ON favorites
  FOR ALL USING (auth.uid() = user_id);

-- Subscriptions readable by owner only
ALTER TABLE subscriptions ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Own subscription" ON subscriptions
  FOR SELECT USING (auth.uid() = user_id);
```

---

### 2.2 Auth Service

**Flow:** Anonymous first → Google Sign-In → persist favorites + Pro status

```typescript
// auth.service.ts
export class AuthService {
  private supabase = inject(SupabaseService).client;
  user = signal<User | null>(null);
  isPro = signal<boolean>(false);

  async signInWithGoogle() {
    await this.supabase.auth.signInWithOAuth({
      provider: 'google',
      options: { redirectTo: window.location.origin }
    });
  }

  async signOut() {
    await this.supabase.auth.signOut();
  }

  async checkProStatus() {
    const { data } = await this.supabase
      .from('subscriptions')
      .select('status, expires_at')
      .eq('status', 'active')
      .single();
    this.isPro.set(!!data && new Date(data.expires_at) > new Date());
  }
}
```

---

### 2.3 Push Notifications (Daily Prompt)

**Dependencies:**
```bash
npm install @capacitor-firebase/messaging
npx cap sync
```

**Implementation:**
- Firebase project → FCM setup for Android & iOS
- `notification.service.ts` — request permission, get token, send to Supabase `device_tokens` table
- Backend: Supabase Edge Function (cron, daily 8AM IST) picks a random prompt → sends FCM push
- Deep link payload: `{ promptId: 42 }` → opens prompt-details on tap

**Notification content template:**
```
Title: "Today's Prompt ✨"
Body: "New {category} prompt ready — tap to copy in {userLanguage}"
```

---

## Phase 3 — Monetization (Week 11–12)

### 3.1 Pro Subscription (Razorpay)

**Why Razorpay:** Best Indian payment gateway, supports UPI, cards, net banking. Free to integrate.

**Dependencies:**
```bash
npm install razorpay  # backend SDK (Supabase Edge Function)
```

**Frontend flow:**
1. User taps "Upgrade to Pro" (₹49/month or ₹399/year)
2. App calls Supabase Edge Function to create Razorpay order
3. Open Razorpay checkout via `@capacitor/browser`
4. On success webhook → update `subscriptions` table
5. `AuthService.checkProStatus()` re-runs → `isPro` signal updates
6. All Pro gates in app react instantly via signal

**Pro gates to implement:**
- Unlimited prompt copies (remove ad gate)
- Access to `is_premium: true` prompts
- No interstitial ads
- Early access badge on new prompts
- Cloud sync for favorites across devices

---

### 3.2 Ad Experience Tuning

Update `admob.service.ts`:

```typescript
// Change interstitial trigger: every 5 → every 8 clicks
private readonly INTERSTITIAL_THRESHOLD = 8;  // was 5

// Change reward unlock: 5 copies → 10 copies
private readonly REWARD_COPIES_GRANTED = 10;  // was 5

// Skip ads entirely for Pro users
if (this.authService.isPro()) return;
```

---

## Phase 4 — SEO & Web (Week 13–14)

### 4.1 Static Landing Page

Create `src/assets/landing/index.html` — a standalone HTML page (no Angular) served at the root:

**Sections:**
1. Hero — App name, tagline, download badges (Play Store + App Store)
2. Feature highlights — 6 language support, categories, copy feature
3. Screenshot gallery — 3–4 app screenshots
4. Sample prompts grid (static HTML, crawlable by Google)
5. Footer — Privacy policy, contact, social links

**SEO targets for this page:**
- `<h1>AI Image Prompt Gallery in Tamil, Hindi, Telugu</h1>`
- Structured data: `SoftwareApplication` schema markup
- Canonical URL, sitemap.xml, robots.txt

---

### 4.2 Per-Prompt Meta Tags (Angular SSR or Prerendering)

**Option A (Easier): Angular Prerendering**
```bash
ng add @angular/ssr
```
Configure `angular.json` to prerender all prompt routes using the prompts JSON as a route list.

**Option B (Simpler): Dynamic Meta via Angular `Meta` service**
```typescript
// In prompt-details component
this.meta.updateTag({ property: 'og:title', content: `${prompt.category} AI Prompt #${prompt.id}` });
this.meta.updateTag({ property: 'og:description', content: prompt.prompt.slice(0, 160) });
this.meta.updateTag({ property: 'og:image', content: prompt.url });
```

---

## Phase 5 — Content Pipeline (Ongoing)

### 5.1 Prompt Management Admin

Build a minimal internal admin tool (simple HTML page or Supabase Studio):
- Add new prompts with image upload to Supabase Storage
- Auto-generate translations via Gemini API (translate prompt to 5 Indian languages)
- Set category, difficulty, AI tools, is_premium flags
- Publish → triggers CDN revalidation

**Translation automation (Supabase Edge Function):**
```typescript
// translate-prompt/index.ts
const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', {
  body: JSON.stringify({
    contents: [{ parts: [{ text: `Translate this AI image prompt to Tamil, Hindi, Telugu, Kannada, Malayalam. Return JSON with keys: ta, hi, te, kn, ml.\n\nPrompt: ${prompt}` }] }]
  })
});
```

---

## Dependency Installation Summary

```bash
# Phase 1
npm install @capacitor/share

# Phase 2
npm install @supabase/supabase-js
npm install @capacitor-firebase/messaging

# Phase 3
# Razorpay is browser-based checkout — no npm package needed for frontend
# Backend SDK installed in Supabase Edge Functions only

# Phase 4
ng add @angular/ssr
```

---

## Risk Register

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| AdMob policy violation (too many ads) | Medium | High | Follow AdMob frequency guidelines, reduce interstitial rate |
| Supabase free tier limits (500MB DB) | Low | Medium | Use DB for metadata only, store images in Supabase Storage |
| Razorpay Play Store rejection | Medium | High | Follow Google Play billing policy — use Google Play Billing for Android subscriptions |
| iOS App Store rejection (payments) | High | High | Use Apple IAP for iOS Pro tier, Razorpay only for web |
| FCM token expiry | Low | Low | Re-fetch token on app resume, store updated token |
| Angular SSR + Capacitor conflict | Medium | Medium | Keep SSR for web only, mobile uses static build |

> **Critical Note on Payments:** Google Play and Apple App Store **require** use of their native billing systems for in-app subscriptions. Razorpay can only be used for the web version. Use `@capacitor/google-pay` or the native Capacitor IAP plugin for Android/iOS.

---

## Tech Debt to Address

| Item | File | Action |
|------|------|--------|
| `data.ts` service name | `src/app/services/data.ts` | Rename to `data.service.ts` |
| `GeminiRef` interface | Scattered across components | Extract to `prompt.model.ts` |
| AdMob service in nested folder | `src/app/services/admob/` | Move to `src/app/core/services/` |
| Inline ad logic in components | `home`, `prompt-details` | Move to `AdmobService` methods |
| `image-modal` component (legacy) | `src/app/components/image-modal/` | Remove if unused |
| `localStorage` spread across components | Multiple | Centralize into dedicated services |
| Hard-coded copy limit (3) | `prompt-details` | Move to config constant |

---

## Development Milestones

| Milestone | Target | Deliverables |
|-----------|--------|--------------|
| **M0 — Foundation** | Week 2 | Brand update, project restructure, data model extended |
| **M1 — Share + Favorites** | Week 4 | Share card, bookmarks, copy count, "New This Week" |
| **M2 — Filters + UX** | Week 6 | AI tool filter, difficulty tags, "Try With" deep links |
| **M3 — Backend** | Week 9 | Supabase live, auth, cloud favorites, copy tracking |
| **M4 — Notifications** | Week 10 | FCM push, daily prompt, deep link on tap |
| **M5 — Pro Tier** | Week 12 | Razorpay (web) + native IAP (mobile), Pro gates live |
| **M6 — SEO** | Week 14 | Landing page, SSR prerendering, meta tags, sitemap |
| **M7 — Admin + Pipeline** | Week 16 | Prompt CMS, auto-translation, content at 500+ prompts |

---

## Definition of Done (Per Feature)

- [ ] Feature works on Web (Chrome)
- [ ] Feature works on Android (real device)
- [ ] Feature works on iOS simulator
- [ ] No new lint errors (`ng lint`)
- [ ] No console errors in production build
- [ ] Admob ads still load correctly
- [ ] Performance budget not exceeded (`ng build --stats-json`)
- [ ] `ng test` passes

---

*Document based on marketing priorities in MARKETING_PLAN.md and codebase analysis of Angular 21 + Capacitor 8 project.*
