import { Injectable } from '@angular/core';
import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';
import { Prompt } from '../models/prompt.model';

const WEB_BASE_URL = 'https://codingtamilan.in/gemini-muse/#/prompt/';

@Injectable({
  providedIn: 'root'
})
export class ShareService {

  async sharePrompt(prompt: Prompt, language: string = 'en'): Promise<void> {
    const promptText = this.getPromptText(prompt, language);
    const url = `${WEB_BASE_URL}${prompt.id}`;
    const title = 'Check out this AI image prompt!';
    const text = `${promptText.slice(0, 120)}...\n\nFind more at Gemini Muse`;

    if (Capacitor.isNativePlatform()) {
      try {
        await Share.share({ title, text, url, dialogTitle: 'Share this prompt' });
        return;
      } catch {
        // User cancelled or share failed — fall through to web
      }
    }

    // Web Share API fallback
    if (navigator.share) {
      try {
        await navigator.share({ title, text, url });
        return;
      } catch {
        // User cancelled — silent fail
      }
    }

    // Last resort: copy URL to clipboard
    await navigator.clipboard.writeText(url);
  }

  private getPromptText(prompt: Prompt, language: string): string {
    switch (language) {
      case 'hi': return prompt.prompt_hi || prompt.prompt;
      case 'ta': return prompt.prompt_tn || prompt.prompt;
      case 'te': return prompt.prompt_te || prompt.prompt;
      case 'kn': return prompt.prompt_kn || prompt.prompt;
      case 'ml': return prompt.prompt_ml || prompt.prompt;
      default:   return prompt.prompt;
    }
  }
}
