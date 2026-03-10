import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';

const DEVICE_ID_KEY = 'geminimuse_device_id';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private http = inject(HttpClient);
  private base = environment.apiUrl;

  // ── Device ID ─────────────────────────────────────────────────
  // Generated once per install, persisted in localStorage.
  // Used as anonymous user identifier — no login required.
  readonly deviceId: string = this.getOrCreateDeviceId();

  private getOrCreateDeviceId(): string {
    let id = localStorage.getItem(DEVICE_ID_KEY);
    if (!id || !this.isValidUuid(id)) {
      id = this.generateUuid();
      localStorage.setItem(DEVICE_ID_KEY, id);
    }
    return id;
  }

  private generateUuid(): string {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
      const r = (Math.random() * 16) | 0;
      const v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  private isValidUuid(id: string): boolean {
    return /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(id);
  }

  // ── Favorites ─────────────────────────────────────────────────

  async getFavorites(): Promise<number[]> {
    try {
      const res = await firstValueFrom(
        this.http.get<{ favorites: number[] }>(
          `${this.base}/favorites.php?device_id=${this.deviceId}`
        )
      );
      return res.favorites ?? [];
    } catch {
      return [];
    }
  }

  async toggleFavorite(promptId: number): Promise<number[]> {
    try {
      const res = await firstValueFrom(
        this.http.post<{ favorites: number[] }>(`${this.base}/favorites.php`, {
          device_id: this.deviceId,
          prompt_id: promptId,
        })
      );
      return res.favorites ?? [];
    } catch {
      return [];
    }
  }

  // ── Copy Tracking ─────────────────────────────────────────────

  async recordCopy(promptId: number, language: string): Promise<number> {
    try {
      const res = await firstValueFrom(
        this.http.post<{ copy_count: number }>(`${this.base}/copy.php`, {
          device_id: this.deviceId,
          prompt_id: promptId,
          language,
        })
      );
      return res.copy_count ?? 0;
    } catch {
      return 0;
    }
  }

  // ── Stats ─────────────────────────────────────────────────────

  async getPromptStats(promptId: number): Promise<number> {
    try {
      const res = await firstValueFrom(
        this.http.get<{ copy_count: number }>(
          `${this.base}/stats.php?id=${promptId}`
        )
      );
      return res.copy_count ?? 0;
    } catch {
      return 0;
    }
  }

  async getBulkStats(promptIds: number[]): Promise<Record<number, number>> {
    if (!promptIds.length) return {};
    try {
      const ids = promptIds.join(',');
      const res = await firstValueFrom(
        this.http.get<Record<number, number>>(
          `${this.base}/stats.php?ids=${ids}`
        )
      );
      return res ?? {};
    } catch {
      return {};
    }
  }
}
