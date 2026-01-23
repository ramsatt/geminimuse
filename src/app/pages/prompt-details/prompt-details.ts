import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { DataService } from '../../services/data';
import { Clipboard } from '@capacitor/clipboard';
import { AdmobService } from '../../services/admob/admob.service';

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
  private admobService = inject(AdmobService);

  id = signal<string | null>(null);
  
  constructor() {
    // Listen to route changes to update ID and scroll to top
    this.route.paramMap.subscribe(params => {
      this.id.set(params.get('id'));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  
  // Find image from service based on ID
  image = computed(() => {
    const idStr = this.id();
    const id = parseInt(idStr || '0', 10);
    return this.dataService.items().find(i => i.id === id);
  });

  selectedLanguage = signal<string>('en');
  copyStatus = signal<string>('Copy Prompt');
  
  // Free copies tracking
  freeCopiesRemaining = signal<number>(this.getFreeCopies());
  showRewardPrompt = signal<boolean>(false);

  readonly languages = [
    { code: 'en', label: 'English', icon: 'En' },
    { code: 'hi', label: 'Hindi', icon: 'अ' },
    { code: 'ta', label: 'Tamil', icon: 'அ' },
    { code: 'te', label: 'Telugu', icon: 'తె' },
    { code: 'kn', label: 'Kannada', icon: 'ಕ' },
    { code: 'ml', label: 'Malayalam', icon: 'മ' }
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
      default: return img.prompt;
    }
  });

  // Related Prompts Logic
  relatedPrompts = computed(() => {
    const current = this.image();
    if (!current) return [];
    
    const allItems = this.dataService.items();
    const currentId = current.id;
    
    // Extract keywords from current prompt
    const keywords = current.prompt
      .toLowerCase()
      .split(' ')
      .filter(word => word.length > 4)
      .slice(0, 5);
    
    // Find similar prompts
    const scored = allItems
      .filter(item => item.id !== currentId)
      .map(item => {
        const promptLower = item.prompt.toLowerCase();
        const score = keywords.reduce((acc, keyword) => {
          return acc + (promptLower.includes(keyword) ? 1 : 0);
        }, 0);
        return { item, score };
      })
      .filter(({ score }) => score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 6)
      .map(({ item }) => item);
    
    // Fallback to random if no matches
    if (scored.length === 0) {
      return allItems
        .filter(item => item.id !== currentId)
        .sort(() => Math.random() - 0.5)
        .slice(0, 6);
    }
    
    return scored;
  });

  private getFreeCopies(): number {
    const stored = localStorage.getItem('freeCopiesRemaining');
    if (stored === null) {
      // First time - give 3 free copies
      localStorage.setItem('freeCopiesRemaining', '3');
      return 3;
    }
    return parseInt(stored, 10);
  }

  private updateFreeCopies(count: number) {
    this.freeCopiesRemaining.set(count);
    localStorage.setItem('freeCopiesRemaining', count.toString());
  }

  setLanguage(code: string) {
    this.selectedLanguage.set(code);
  }

  async copyToClipboard() {
    const text = this.currentPrompt();
    if (!text) return;

    // Check if user has free copies remaining
    if (this.freeCopiesRemaining() <= 0) {
      // Show reward ad prompt
      this.showRewardPrompt.set(true);
      return;
    }

    // Perform copy
    await Clipboard.write({
      string: text
    });

    // Decrease free copies
    this.updateFreeCopies(this.freeCopiesRemaining() - 1);

    this.copyStatus.set('Copied!');
    
    // Show interstitial ad after copy (only if they still have copies left)
    setTimeout(async () => {
      if (this.freeCopiesRemaining() > 0) {
        await this.admobService.showInterstitial();
      }
      this.copyStatus.set('Copy Prompt');
    }, 1500);
  }

  async watchAdForCopies() {
    this.showRewardPrompt.set(false);
    
    // Show reward video ad
    const success = await this.admobService.showRewardVideo();
    
    if (success) {
      // Grant 5 more copies
      this.updateFreeCopies(5);
      this.copyStatus.set('Unlocked! 🎉');
      setTimeout(() => {
        this.copyStatus.set('Copy Prompt');
      }, 2000);
    } else {
      // Ad failed or was skipped
      this.copyStatus.set('Copy Prompt');
    }
  }

  closeRewardPrompt() {
    this.showRewardPrompt.set(false);
  }

  openRelatedPrompt(id: number) {
    this.router.navigate(['/prompt', id]);
  }

  goBack() {
    this.router.navigate(['/']);
  }
}
