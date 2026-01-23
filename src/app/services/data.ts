import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

export interface GeminiRef {
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
}

@Injectable({
  providedIn: 'root'
})
export class DataService {
  items = signal<GeminiRef[]>([]);
  loaded = signal<boolean>(false);

  constructor(private http: HttpClient) {
    this.loadData();
  }

  async loadData() {
    try {
      // Use relative path for assets
      const data = await firstValueFrom(this.http.get<GeminiRef[]>('assets/data/prompts.json'));
      
      // Shuffle the data
      const shuffled = this.shuffleArray(data);
      
      this.items.set(shuffled);
      this.loaded.set(true);
    } catch (e) {
      console.error('Failed to load prompts data', e);
    }
  }

  private shuffleArray(array: any[]) {
    for (let i = array.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
  }
}
