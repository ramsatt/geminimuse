import { Injectable } from '@angular/core';
import { Functions, httpsCallable } from '@angular/fire/functions';
import { inject } from '@angular/core';

export interface TranslateRequest {
    promptText: string;
}

export interface TranslateResponse {
    translations: {
        ta: string;
        hi: string;
        te: string;
        kn: string;
        ml: string;
    };
}

/**
 * TranslateService — calls the `translatePrompt` Firebase Cloud Function.
 *
 * The Gemini API key is stored securely in Firebase Secret Manager and never
 * exposed to the client. The Cloud Function handles the Gemini API call
 * server-side and returns the translated strings.
 */
@Injectable({ providedIn: 'root' })
export class TranslateService {
    private functions = inject(Functions);

    async translate(promptText: string): Promise<TranslateResponse['translations']> {
        const fn = httpsCallable<TranslateRequest, TranslateResponse>(
            this.functions,
            'translatePrompt'
        );
        const result = await fn({ promptText });
        return result.data.translations;
    }
}
