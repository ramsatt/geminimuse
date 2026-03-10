import { Injectable, computed, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { Prompt } from '../models/prompt.model';

// Update this list each week to surface new prompts.
// These IDs are marked is_new = true at load time without touching the JSON.
const NEW_PROMPT_IDS = [1, 2, 3, 5, 8, 13, 21, 34, 55, 89];

@Injectable({
  providedIn: 'root'
})
export class DataService {
  items = signal<Prompt[]>([]);
  loaded = signal<boolean>(false);

  newPrompts = computed(() =>
    // Preserve insertion order for the "New This Week" row
    NEW_PROMPT_IDS
      .map(id => this.items().find(p => p.id === id))
      .filter((p): p is Prompt => p !== undefined)
  );

  constructor(private http: HttpClient) {
    this.loadData();
  }

  async loadData() {
    try {
      const data = await firstValueFrom(this.http.get<Prompt[]>('assets/data/prompts.json'));
      const marked = data.map(p => ({
        ...p,
        is_new: NEW_PROMPT_IDS.includes(p.id)
      }));
      this.items.set(this.shuffleArray(marked));
      this.loaded.set(true);
    } catch (e) {
      console.error('Failed to load prompts data', e);
    }
  }

  getById(id: number): Prompt | undefined {
    return this.items().find(i => i.id === id);
  }

  private shuffleArray(array: Prompt[]): Prompt[] {
    const arr = [...array];
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }
}
