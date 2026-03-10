<?php

namespace app\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Translation API — calls Gemini 1.5 Flash
 *
 * POST /translate {prompt_text: "..."}
 *      → {translations: {ta: "...", hi: "...", te: "...", kn: "...", ml: "..."}}
 */
class TranslateController extends BaseApiController
{
    public function actionIndex(): array
    {
        $text = trim(Yii::$app->request->getBodyParam('prompt_text', ''));
        if ($text === '') {
            throw new BadRequestHttpException('prompt_text is required');
        }

        $apiKey = Yii::$app->params['geminiApiKey'];
        if ($apiKey === 'YOUR_GEMINI_API_KEY' || $apiKey === '') {
            throw new ServerErrorHttpException('Gemini API key not configured');
        }

        $translations = $this->callGemini($text, $apiKey);

        return ['translations' => $translations];
    }

    private function callGemini(string $promptText, string $apiKey): array
    {
        $instruction = <<<PROMPT
You are an expert AI prompt translator.
Translate the following AI image generation prompt into Tamil, Hindi, Telugu, Kannada, and Malayalam.

Rules:
1. Keep technical AI terms (cinematic, bokeh, 8K, hyper-realistic, golden hour) in English.
2. Translate all descriptive parts naturally into each language.
3. Return ONLY a valid JSON object with these exact keys: "ta", "hi", "te", "kn", "ml".
4. No markdown, no explanation — raw JSON only.

Prompt to translate:
{$promptText}
PROMPT;

        $body = json_encode([
            'contents'         => [['parts' => [['text' => $instruction]]]],
            'generationConfig' => [
                'temperature'     => 0.2,
                'maxOutputTokens' => 2048,
            ],
        ]);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode !== 200) {
            Yii::error("Gemini API failed. HTTP $httpCode. Response: $response", __METHOD__);
            throw new ServerErrorHttpException("Translation service error (HTTP $httpCode)");
        }

        $data = json_decode($response, true);
        $raw  = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Strip markdown code fences if model wraps in ```json … ```
        $raw = preg_replace('/^```json\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/',      '', $raw);

        $translations = json_decode(trim($raw), true);

        if (!is_array($translations) || !array_key_exists('ta', $translations)) {
            Yii::error("Unexpected Gemini response: $raw", __METHOD__);
            throw new ServerErrorHttpException('Translation service returned unexpected format');
        }

        // Sanitize: ensure all five keys exist
        foreach (['ta', 'hi', 'te', 'kn', 'ml'] as $lang) {
            $translations[$lang] = (string)($translations[$lang] ?? $promptText);
        }

        return $translations;
    }
}
