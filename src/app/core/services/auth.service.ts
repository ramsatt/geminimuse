import { Injectable, signal, inject } from '@angular/core';
import { Auth, signInAnonymously, onAuthStateChanged, User } from '@angular/fire/auth';

/**
 * AuthService — manages anonymous Firebase authentication.
 *
 * On app start, the user is automatically signed in anonymously.
 * The Firebase UID replaces the old localStorage device-id UUID as the
 * stable per-device identity. Both remain compatible: the old device_id
 * is kept in localStorage for the legacy Yii2 fallback during migration.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
    private auth = inject(Auth);

    /** Currently signed-in user (null while initialising). */
    readonly user = signal<User | null>(null);

    /** Firebase UID, available once sign-in completes. */
    get uid(): string | null {
        return this.user()?.uid ?? null;
    }

    constructor() {
        // Track auth state changes
        onAuthStateChanged(this.auth, (u) => this.user.set(u));

        // Auto sign-in anonymously on first load
        this.ensureSignedIn();
    }

    private async ensureSignedIn(): Promise<void> {
        if (!this.auth.currentUser) {
            try {
                await signInAnonymously(this.auth);
            } catch (err) {
                console.error('[AuthService] Anonymous sign-in failed:', err);
            }
        }
    }
}
