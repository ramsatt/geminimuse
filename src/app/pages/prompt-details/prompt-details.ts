import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { Meta, Title } from '@angular/platform-browser';
import { DataService } from '../../core/services/data.service';
import { AdmobService } from '../../core/services/admob.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { ShareService } from '../../core/services/share.service';
import { ApiService } from '../../core/services/api.service';
import { Prompt } from '../../core/models/prompt.model';
import { Clipboard } from '@capacitor/clipboard';

const APP_BASE_URL = 'https://codingtamilan.in/gemini-muse';

const FREE_COPIES_KEY = 'freeCopiesRemaining';
const FREE_COPIES_DEFAULT = 3;

const TRY_WITH_TOOLS = [
  { label: 'ChatGPT',    url: 'https://chat.openai.com/',       icon: '✦' },
  { label: 'Gemini',     url: 'https://gemini.google.com/app',  icon: '✧' },
  { label: 'Midjourney', url: 'https://www.midjourney.com/',    icon: '◈' },
  { label: 'Firefly',    url: 'https://firefly.adobe.com/',     icon: '◉' },
];

@Component({
  selector: 'app-prompt-details',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './prompt-details.html',
})
export class PromptDetailsPage {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private dataService = inject(DataService);
  readonly admobService = inject(AdmobService);
  private favoritesService = inject(FavoritesService);
  private shareService = inject(ShareService);
  private api = inject(ApiService);
  private meta = inject(Meta);
  private titleService = inject(Title);

  id = signal<string | null>(null);
  shareStatus = signal<'idle' | 'sharing' | 'done'>('idle');
  copyCount = signal<number>(0);

  readonly tryWithTools = TRY_WITH_TOOLS;

  constructor() {
    this.route.paramMap.subscribe(params => {
      this.id.set(params.get('id'));
      window.scrollTo({ top: 0, behavior: 'smooth' });
      this.loadCopyCount();
      // Defer until signal resolves on next tick
      setTimeout(() => this.updatePageMeta(), 0);
    });
  }

  image = computed(() => {
    const id = parseInt(this.id() || '0', 10);
    return this.dataService.items().find(i => i.id === id);
  });

  isFavorite = computed(() => {
    const img = this.image();
    return img ? this.favoritesService.isFavorite(img.id) : false;
  });

  selectedLanguage = signal<string>('en');
  copyStatus = signal<string>('Copy Prompt');

  freeCopiesRemaining = signal<number>(this.getFreeCopies());
  showRewardPrompt = signal<boolean>(false);

  readonly languages = [
    { code: 'en', label: 'English',   icon: 'En' },
    { code: 'hi', label: 'Hindi',     icon: 'अ'  },
    { code: 'ta', label: 'Tamil',     icon: 'அ'  },
    { code: 'te', label: 'Telugu',    icon: 'తె'  },
    { code: 'kn', label: 'Kannada',   icon: 'ಕ'  },
    { code: 'ml', label: 'Malayalam', icon: 'മ'  }
  ];

  currentPrompt = computed(() => {
    const img = this.image();
    const lang = this.selectedLanguage();
    if (!img) return '';
    switch (lang) {
      case 'hi': return img.prompt_hi || img.prompt;
      case 'ta': return img.prompt_tn || img.prompt;
      case 'te': return img.prompt_te || img.prompt;
      case 'kn': return img.prompt_kn || img.prompt;
      case 'ml': return img.prompt_ml || img.prompt;
      default:   return img.prompt;
    }
  });

  relatedPrompts = computed(() => {
    const current = this.image();
    if (!current) return [];

    const allItems = this.dataService.items();
    const keywords = current.prompt
      .toLowerCase().split(' ')
      .filter(w => w.length > 4).slice(0, 5);

    const scored = allItems
      .filter(item => item.id !== current.id)
      .map(item => ({
        item,
        score: keywords.reduce((acc, kw) => acc + (item.prompt.toLowerCase().includes(kw) ? 1 : 0), 0)
      }))
      .filter(({ score }) => score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 6)
      .map(({ item }) => item);

    return scored.length > 0
      ? scored
      : allItems.filter(i => i.id !== current.id).sort(() => Math.random() - 0.5).slice(0, 6);
  });

  // ── Page Meta Tags ───────────────────────────────────────────────
  private updatePageMeta(): void {
    const img = this.image();
    if (!img) return;

    const category = img.category ?? 'AI Image';
    const snippet  = img.prompt.slice(0, 155);
    const pageUrl  = `${APP_BASE_URL}/#/prompt/${img.id}`;
    const title    = `${category} AI Prompt #${img.id} — Gemini Muse`;
    const desc     = `${snippet}… Copy this ${category.toLowerCase()} AI prompt in Tamil, Hindi, Telugu, Kannada, Malayalam or English on Gemini Muse.`;

    this.titleService.setTitle(title);

    const tags: { name?: string; property?: string; content: string }[] = [
      { name: 'description',          content: desc },
      { property: 'og:title',         content: title },
      { property: 'og:description',   content: desc },
      { property: 'og:image',         content: img.url },
      { property: 'og:url',           content: pageUrl },
      { property: 'og:type',          content: 'article' },
      { name: 'twitter:card',         content: 'summary_large_image' },
      { name: 'twitter:title',        content: title },
      { name: 'twitter:description',  content: desc },
      { name: 'twitter:image',        content: img.url },
    ];

    for (const tag of tags) {
      if (tag.name)     this.meta.updateTag({ name: tag.name, content: tag.content });
      if (tag.property) this.meta.updateTag({ property: tag.property, content: tag.content });
    }
  }

  // ── Copy Count ──────────────────────────────────────────────────
  private async loadCopyCount(): Promise<void> {
    const img = this.image();
    if (!img) return;
    const count = await this.api.getPromptStats(img.id);
    this.copyCount.set(count);
  }

  formatCount(n: number): string {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
    if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K';
    return String(n);
  }

  // ── Favorites ───────────────────────────────────────────────────
  toggleFavorite(): void {
    const img = this.image();
    if (img) this.favoritesService.toggle(img.id);
  }

  // ── Share ────────────────────────────────────────────────────────
  async share(): Promise<void> {
    const img = this.image();
    if (!img) return;
    this.shareStatus.set('sharing');
    await this.shareService.sharePrompt(img, this.selectedLanguage());
    this.shareStatus.set('done');
    setTimeout(() => this.shareStatus.set('idle'), 2000);
  }

  // ── Try With ─────────────────────────────────────────────────────
  openTool(url: string): void {
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  // ── Language ─────────────────────────────────────────────────────
  setLanguage(code: string): void {
    this.selectedLanguage.set(code);
  }

  // ── Copy ─────────────────────────────────────────────────────────
  async copyToClipboard(): Promise<void> {
    const text = this.currentPrompt();
    if (!text) return;

    if (this.freeCopiesRemaining() <= 0) {
      this.showRewardPrompt.set(true);
      return;
    }

    await Clipboard.write({ string: text });
    this.updateFreeCopies(this.freeCopiesRemaining() - 1);
    this.copyStatus.set('Copied!');

    // Record copy in backend (fire-and-forget, update local count)
    const img = this.image();
    if (img) {
      const newCount = await this.api.recordCopy(img.id, this.selectedLanguage());
      if (newCount > 0) this.copyCount.set(newCount);
    }

    setTimeout(async () => {
      if (this.freeCopiesRemaining() > 0) {
        await this.admobService.showInterstitial();
      }
      this.copyStatus.set('Copy Prompt');
    }, 1500);
  }

  async watchAdForCopies(): Promise<void> {
    this.showRewardPrompt.set(false);
    const success = await this.admobService.showRewardVideo();
    if (success) {
      this.updateFreeCopies(this.admobService.REWARD_COPIES_GRANTED);
      this.copyStatus.set('Unlocked!');
      setTimeout(() => this.copyStatus.set('Copy Prompt'), 2000);
    } else {
      this.copyStatus.set('Copy Prompt');
    }
  }

  closeRewardPrompt(): void {
    this.showRewardPrompt.set(false);
  }

  // ── Navigation ───────────────────────────────────────────────────
  openRelatedPrompt(id: number): void {
    this.router.navigate(['/prompt', id]);
  }

  goBack(): void {
    this.router.navigate(['/']);
  }

  // ── Helpers ──────────────────────────────────────────────────────
  private getFreeCopies(): number {
    const stored = localStorage.getItem(FREE_COPIES_KEY);
    if (stored === null) {
      localStorage.setItem(FREE_COPIES_KEY, String(FREE_COPIES_DEFAULT));
      return FREE_COPIES_DEFAULT;
    }
    return parseInt(stored, 10);
  }

  private updateFreeCopies(count: number): void {
    this.freeCopiesRemaining.set(count);
    localStorage.setItem(FREE_COPIES_KEY, String(count));
  }
}
