import { Injectable, inject } from '@angular/core';
import {
    Firestore,
    collection,
    doc,
    setDoc,
    deleteDoc,
    getDocs,
    query,
    where,
    orderBy,
    addDoc,
    getDoc,
    updateDoc,
    increment,
    serverTimestamp,
} from '@angular/fire/firestore';
import { AuthService } from './auth.service';

/** Shape of a favorite document in Firestore. */
export interface FavoriteDoc {
    uid: string;
    promptId: number;
    createdAt: unknown; // Firestore Timestamp
}

/** Shape of a copy-event document in Firestore. */
export interface CopyEventDoc {
    uid: string;
    promptId: number;
    language: string;
    copiedAt: unknown; // Firestore Timestamp
}

/**
 * FirestoreService — the single gateway to all Firestore operations.
 *
 * Collections:
 *  - favorites/{uid}_{promptId}   → user favourites
 *  - copy_events/{auto-id}        → copy tracking events
 *  - stats/global                 → aggregate counters
 *  - images/{auto-id}             → Google Drive image references
 */
@Injectable({ providedIn: 'root' })
export class FirestoreService {
    private db = inject(Firestore);
    private authService = inject(AuthService);

    // ── Helpers ────────────────────────────────────────────────────────────────

    private get uid(): string {
        const id = this.authService.uid;
        if (!id) throw new Error('User not authenticated yet');
        return id;
    }

    private favDocId(promptId: number): string {
        return `${this.uid}_${promptId}`;
    }

    // ── Favorites ──────────────────────────────────────────────────────────────

    /**
     * Returns all favorited prompt IDs for the current user.
     */
    async getFavoriteIds(): Promise<number[]> {
        try {
            const ref = collection(this.db, 'favorites');
            const q = query(
                ref,
                where('uid', '==', this.uid),
                orderBy('createdAt', 'desc')
            );
            const snap = await getDocs(q);
            return snap.docs.map((d) => (d.data() as FavoriteDoc).promptId);
        } catch {
            return [];
        }
    }

    /**
     * Toggles a favorite: adds if missing, removes if present.
     * Returns the updated list of favorite IDs.
     */
    async toggleFavorite(promptId: number): Promise<number[]> {
        const docRef = doc(this.db, 'favorites', this.favDocId(promptId));
        const existing = await getDoc(docRef);

        if (existing.exists()) {
            await deleteDoc(docRef);
        } else {
            const data: FavoriteDoc = {
                uid: this.uid,
                promptId,
                createdAt: serverTimestamp(),
            };
            await setDoc(docRef, data);
        }

        return this.getFavoriteIds();
    }

    // ── Copy Tracking ──────────────────────────────────────────────────────────

    /**
     * Records a copy event and increments the global stats counter.
     */
    async recordCopy(promptId: number, language: string): Promise<void> {
        try {
            const event: CopyEventDoc = {
                uid: this.uid,
                promptId,
                language,
                copiedAt: serverTimestamp(),
            };
            await addDoc(collection(this.db, 'copy_events'), event);

            // Increment global stats counter
            const statsRef = doc(this.db, 'stats', 'global');
            await updateDoc(statsRef, { totalCopies: increment(1) }).catch(async () => {
                // Document might not exist yet — create it
                await setDoc(statsRef, {
                    totalCopies: 1,
                    totalFavorites: 0,
                    totalTranslations: 0,
                });
            });
        } catch (err) {
            console.error('[FirestoreService] recordCopy failed:', err);
        }
    }

    /**
     * Returns the copy count for a specific prompt.
     */
    async getPromptCopyCount(promptId: number): Promise<number> {
        try {
            const ref = collection(this.db, 'copy_events');
            const q = query(ref, where('promptId', '==', promptId));
            const snap = await getDocs(q);
            return snap.size;
        } catch {
            return 0;
        }
    }

    /**
     * Returns copy counts for multiple prompts as a Record<promptId, count>.
     */
    async getBulkCopyCounts(promptIds: number[]): Promise<Record<number, number>> {
        if (!promptIds.length) return {};
        try {
            // Firestore 'in' supports up to 30 items per query
            const chunks: number[][] = [];
            for (let i = 0; i < promptIds.length; i += 30) {
                chunks.push(promptIds.slice(i, i + 30));
            }

            const results: Record<number, number> = {};
            for (const chunk of chunks) {
                const ref = collection(this.db, 'copy_events');
                const q = query(ref, where('promptId', 'in', chunk));
                const snap = await getDocs(q);
                snap.docs.forEach((d) => {
                    const { promptId } = d.data() as CopyEventDoc;
                    results[promptId] = (results[promptId] ?? 0) + 1;
                });
            }
            return results;
        } catch {
            return {};
        }
    }

    // ── Images (Google Drive references) ──────────────────────────────────────

    /**
     * Saves a Google Drive image reference to Firestore after upload.
     */
    async saveImageRef(promptId: number, fileId: string, driveUrl: string): Promise<void> {
        await addDoc(collection(this.db, 'images'), {
            uid: this.uid,
            promptId,
            fileId,
            driveUrl,
            uploadedAt: serverTimestamp(),
        });
    }

    /**
     * Returns all image references for a given prompt.
     */
    async getImagesForPrompt(promptId: number): Promise<{ fileId: string; driveUrl: string }[]> {
        try {
            const ref = collection(this.db, 'images');
            const q = query(ref, where('promptId', '==', promptId), orderBy('uploadedAt', 'desc'));
            const snap = await getDocs(q);
            return snap.docs.map((d) => ({
                fileId: d.data()['fileId'],
                driveUrl: d.data()['driveUrl'],
            }));
        } catch {
            return [];
        }
    }
}
