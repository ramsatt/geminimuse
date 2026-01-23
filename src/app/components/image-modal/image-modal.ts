import { Component, Input, Output, EventEmitter, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GeminiRef } from '../../services/data';
import { Clipboard } from '@capacitor/clipboard';

@Component({
  selector: 'app-image-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './image-modal.html',
  styleUrls: ['./image-modal.css']
})
export class ImageModalComponent {
  @Input() set image(val: GeminiRef | null) {
    this._image.set(val);
    this.selectedLanguage.set('en'); // Reset to English on new image
  }
  get image() { return this._image(); }
  
  @Output() close = new EventEmitter<void>();

  private _image = signal<GeminiRef | null>(null);
  selectedLanguage = signal<string>('en');
  copyStatus = signal<string>('Copy Prompt');

  readonly languages = [
    { code: 'en', label: 'English' },
    { code: 'hi', label: 'Hindi' },
    { code: 'tn', label: 'Tamil' },
    { code: 'te', label: 'Telugu' },
    { code: 'kn', label: 'Kannada' },
    { code: 'ml', label: 'Malayalam' }
  ];

  currentPrompt = computed(() => {
    const img = this._image();
    const lang = this.selectedLanguage();
    if (!img) return '';

    switch (lang) {
      case 'hi': return img.prompt_hi || img.prompt;
      case 'tn': return img.prompt_tn || img.prompt;
      case 'te': return img.prompt_te || img.prompt;
      case 'kn': return img.prompt_kn || img.prompt;
      case 'ml': return img.prompt_ml || img.prompt;
      default: return img.prompt;
    }
  });

  async copyToClipboard() {
    const text = this.currentPrompt();
    if (!text) return;

    await Clipboard.write({
      string: text
    });

    this.copyStatus.set('Copied!');
    setTimeout(() => {
      this.copyStatus.set('Copy Prompt');
    }, 2000);
  }

  onClose() {
    this.close.emit();
  }

  setLanguage(code: string) {
    this.selectedLanguage.set(code);
  }
}
