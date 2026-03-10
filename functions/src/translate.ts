/**
 * translatePrompt — Callable Cloud Function
 *
 * Calls Gemini 1.5 Flash to translate an AI prompt into 5 Indian languages.
 * The Gemini API key is stored in Firebase Secret Manager, never in client code.
 *
 * Input:  { promptText: string }
 * Output: { translations: { ta, hi, te, kn, ml } }
 */

import { onCall, HttpsError } from 'firebase-functions/v2/https';
import * as logger from 'firebase-functions/logger';
import { defineSecret } from 'firebase-functions/params';
import * as https from 'https';

// Secret defined in Firebase Secret Manager: GEMINI_API_KEY
const geminiApiKey = defineSecret('GEMINI_API_KEY');

interface TranslateRequest {
    promptText: string;
}

interface TranslationResult {
    ta: string;
    hi: string;
    te: string;
    kn: string;
    ml: string;
}

interface TranslateResponse {
    translations: TranslationResult;
}

export const translatePrompt = onCall(
    { secrets: [geminiApiKey] },
    async (request): Promise<TranslateResponse> => {
        const data = request.data as TranslateRequest;
        const { promptText } = data;

        if (!promptText || promptText.trim() === '') {
            throw new HttpsError('invalid-argument', 'promptText is required');
        }

        const apiKey = geminiApiKey.value();
        if (!apiKey) {
            throw new HttpsError('internal', 'Gemini API key not configured');
        }

        const instruction = `You are an expert AI prompt translator.
Translate the following AI image generation prompt into Tamil, Hindi, Telugu, Kannada, and Malayalam.

Rules:
1. Keep technical AI terms (cinematic, bokeh, 8K, hyper-realistic, golden hour) in English.
2. Translate all descriptive parts naturally into each language.
3. Return ONLY a valid JSON object with these exact keys: "ta", "hi", "te", "kn", "ml".
4. No markdown, no explanation — raw JSON only.

Prompt to translate:
${promptText}`;

        const requestBody = JSON.stringify({
            contents: [{ parts: [{ text: instruction }] }],
            generationConfig: { temperature: 0.2, maxOutputTokens: 2048 },
        });

        const raw = await callGemini(apiKey, requestBody);

        // Strip markdown code fences if model wraps in ```json ... ```
        const cleaned = raw
            .trim()
            .replace(/^```json\s*/i, '')
            .replace(/\s*```$/, '');

        let translations: TranslationResult;
        try {
            translations = JSON.parse(cleaned);
        } catch {
            logger.error('Unexpected Gemini response:', raw);
            throw new HttpsError('internal', 'Translation service returned unexpected format');
        }

        // Ensure all five keys exist
        const langs: (keyof TranslationResult)[] = ['ta', 'hi', 'te', 'kn', 'ml'];
        for (const lang of langs) {
            translations[lang] = String(translations[lang] ?? promptText);
        }

        return { translations };
    });

/** Makes an HTTPS POST to Gemini and returns the text content of the first candidate. */
function callGemini(apiKey: string, body: string): Promise<string> {
    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'generativelanguage.googleapis.com',
            path: `/v1beta/models/gemini-1.5-flash:generateContent?key=${apiKey}`,
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(body) },
        };

        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', (chunk: string) => (data += chunk));
            res.on('end', () => {
                if (res.statusCode !== 200) {
                    reject(new Error(`Gemini HTTP ${res.statusCode}: ${data}`));
                    return;
                }
                try {
                    const parsed = JSON.parse(data);
                    const text: string = parsed?.candidates?.[0]?.content?.parts?.[0]?.text ?? '';
                    resolve(text);
                } catch {
                    reject(new Error('Gemini response parse error'));
                }
            });
        });

        req.on('error', reject);
        req.write(body);
        req.end();
    });
}
