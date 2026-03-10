import { Injectable, computed, signal, inject } from '@angular/core';
import { ApiService } from './api.service';

const STORAGE_KEY = 'geminimuse_favorites';

@Injectable({
  providedIn: 'root'
})
export class FavoritesService {
  private api = inject(ApiService);
  private ids = signal<number[]>(this.load());

  readonly favorites = this.ids.asReadonly();
  readonly count = computed(() => this.ids().length);

  constructor() {
    // Sync from backend on startup.
    // localStorage is the fast cache; backend is the source of truth.
    this.syncFromBackend();
  }

  async toggle(id: number): Promise<void> {
    // Optimistic local update for instant UI feedback
    const current = this.ids();
    const optimistic = current.includes(id)
      ? current.filter(f => f !== id)
      : [...current, id];
    this.ids.set(optimistic);
    this.save(optimistic);

    // Sync to backend and reconcile with server state
    const updated = await this.api.toggleFavorite(id);
    if (updated.length > 0) {
      this.ids.set(updated);
      this.save(updated);
    }
  }

  isFavorite(id: number): boolean {
    return this.ids().includes(id);
  }

  clear(): void {
    this.ids.set([]);
    localStorage.removeItem(STORAGE_KEY);
  }

  private async syncFromBackend(): Promise<void> {
    const serverIds = await this.api.getFavorites();
    if (serverIds.length > 0) {
      this.ids.set(serverIds);
      this.save(serverIds);
    }
  }

  private load(): number[] {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
    } catch {
      return [];
    }
  }

  private save(ids: number[]): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
  }
}
