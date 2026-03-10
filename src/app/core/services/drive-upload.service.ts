import { Injectable, inject } from '@angular/core';
import { Functions, httpsCallable } from '@angular/fire/functions';
import { FirestoreService } from './firestore.service';

export interface DriveUploadTokenResponse {
    uploadUrl: string;  // Resumable upload URL (short-lived, ~1 week)
    fileId: string;     // Google Drive file ID
    driveUrl: string;   // Public sharing URL
}

/**
 * DriveUploadService — uploads images to Google Drive via a server-side token.
 *
 * Flow:
 *  1. Call `getDriveUploadToken` Cloud Function → gets a short-lived resumable upload URL
 *  2. PUT the image binary directly to the Google Drive resumable upload URL
 *  3. Save the fileId + driveUrl reference in Firestore via FirestoreService
 *
 * Why a Cloud Function for the token?
 *  The Drive service account credentials must never be in the client bundle.
 *  The Cloud Function holds the SA key in Firebase Secret Manager and returns
 *  only a scoped, short-lived upload URL.
 */
@Injectable({ providedIn: 'root' })
export class DriveUploadService {
    private functions = inject(Functions);
    private firestoreService = inject(FirestoreService);

    /**
     * Uploads an image file to Google Drive for the given prompt.
     * @returns The public Google Drive URL of the uploaded image.
     */
    async uploadImage(file: File, promptId: number): Promise<string> {
        // Step 1: Get upload token from Cloud Function
        const token = await this.getUploadToken(file.name, file.type);

        // Step 2: Upload binary directly to the resumable upload URL
        await fetch(token.uploadUrl, {
            method: 'PUT',
            headers: { 'Content-Type': file.type },
            body: file,
        });

        // Step 3: Save the Drive reference to Firestore
        await this.firestoreService.saveImageRef(promptId, token.fileId, token.driveUrl);

        return token.driveUrl;
    }

    private async getUploadToken(
        fileName: string,
        mimeType: string
    ): Promise<DriveUploadTokenResponse> {
        const fn = httpsCallable<
            { fileName: string; mimeType: string },
            DriveUploadTokenResponse
        >(this.functions, 'getDriveUploadToken');

        const result = await fn({ fileName, mimeType });
        return result.data;
    }
}
