/**
 * getDriveUploadToken — Callable Cloud Function
 *
 * Creates a Google Drive file using a service account and returns a
 * short-lived resumable upload URL. The client uploads the file binary
 * directly to Drive — the service account key never reaches the browser.
 *
 * Input:  { fileName: string, mimeType: string }
 * Output: { uploadUrl: string, fileId: string, driveUrl: string }
 *
 * Setup required:
 *  1. Create a GCP Service Account in the Firebase project
 *  2. Share your target Drive folder with the service account email
 *  3. Store the SA JSON as a Firebase Secret: DRIVE_SERVICE_ACCOUNT_JSON
 *  4. Set the target folder ID as: DRIVE_FOLDER_ID
 */

import { onCall, HttpsError } from 'firebase-functions/v2/https';
import { defineSecret, defineString } from 'firebase-functions/params';
import { google } from 'googleapis';

const driveServiceAccountJson = defineSecret('DRIVE_SERVICE_ACCOUNT_JSON');
const driveFolderId = defineString('DRIVE_FOLDER_ID');

interface TokenRequest {
    fileName: string;
    mimeType: string;
}

interface TokenResponse {
    uploadUrl: string;
    fileId: string;
    driveUrl: string;
}

export const getDriveUploadToken = onCall(
    { secrets: [driveServiceAccountJson] },
    async (request): Promise<TokenResponse> => {
        const data = request.data as TokenRequest;
        const { fileName, mimeType } = data;

        if (!fileName || !mimeType) {
            throw new HttpsError('invalid-argument', 'fileName and mimeType are required');
        }

        // Parse service account credentials from Secret Manager
        let credentials: object;
        try {
            credentials = JSON.parse(driveServiceAccountJson.value());
        } catch {
            throw new HttpsError('internal', 'Drive service account not configured');
        }

        // Authenticate with Google Drive using the service account
        const auth = new google.auth.GoogleAuth({
            credentials,
            scopes: ['https://www.googleapis.com/auth/drive.file'],
        });

        const drive = google.drive({ version: 'v3', auth });

        // Create the file metadata in Drive (empty for now — content uploaded by client)
        const fileRes = await drive.files.create({
            requestBody: {
                name: fileName,
                mimeType,
                parents: [driveFolderId.value()],
            },
            // Request a resumable upload session
            uploadType: 'resumable',
        });

        const fileId = fileRes.data.id;
        if (!fileId) {
            throw new HttpsError('internal', 'Drive file creation failed');
        }

        // Make the file publicly readable via link
        await drive.permissions.create({
            fileId,
            requestBody: { role: 'reader', type: 'anyone' },
        });

        // Build the resumable upload URL by calling the Drive resumable upload API
        const uploadUrlRes = await drive.files.update(
            { fileId, uploadType: 'resumable', requestBody: { mimeType } },
            { responseType: 'stream' }
        );

        // The resumable upload URL is in the Location header of the response
        const uploadUrl = (uploadUrlRes.headers as Record<string, string>)['location'] ?? '';

        const driveUrl = `https://drive.google.com/uc?export=view&id=${fileId}`;

        return { uploadUrl, fileId, driveUrl };
    });
