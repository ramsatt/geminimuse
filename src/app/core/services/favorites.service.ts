import { Injectable, computed, signal, inject } from '@angular/core';
import { FirestoreService } from './firestore.service';
import { AuthService } from './auth.service';
import { toObservable } from '@angular/core/rxjs-interop';
import { filter, take } from 'rxjs/operators';

const STORAGE_KEY = 'geminimuse_favorites';

/**
 * FavoritesService — manages user favourites backed by Firestore.
 *
 * Strategy:
 *  - localStorage is the fast local cache (instant UI, survives page reload)
 *  - Firestore is the source of truth (persists across devices/reinstalls)
 *  - On startup: wait for auth, then sync from Firestore into the local signal
 *  - On toggle: optimistic local update first, then Firestore write
 */
@Injectable({ providedIn: 'root' })
export class FavoritesService {
  private firestoreService = inject(FirestoreService);
  private authService = inject(AuthService);

  private ids = signal<number[]>(this.load());

  readonly favorites = this.ids.asReadonly();
  readonly count = computed(() => this.ids().length);

  constructor() {
    // Wait until anonymous auth is complete before syncing from Firestore
    toObservable(this.authService.user)
      .pipe(
        filter((user) => user !== null),
        take(1)
      )
      .subscribe(() => this.syncFromFirestore());
  }

  async toggle(id: number): Promise<void> {
    // Optimistic local update for instant UI feedback
    const current = this.ids();
    const optimistic = current.includes(id)
      ? current.filter((f) => f !== id)
      : [...current, id];
    this.ids.set(optimistic);
    this.save(optimistic);

    // Sync to Firestore and reconcile with server state
    const updated = await this.firestoreService.toggleFavorite(id);
    this.ids.set(updated);
    this.save(updated);
  }

  isFavorite(id: number): boolean {
    return this.ids().includes(id);
  }

  clear(): void {
    this.ids.set([]);
    localStorage.removeItem(STORAGE_KEY);
  }

  private async syncFromFirestore(): Promise<void> {
    try {
      const serverIds = await this.firestoreService.getFavoriteIds();
      if (serverIds.length > 0 || this.ids().length === 0) {
        this.ids.set(serverIds);
        this.save(serverIds);
      }
    } catch (err) {
      console.warn('[FavoritesService] Firestore sync failed, using local cache:', err);
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
