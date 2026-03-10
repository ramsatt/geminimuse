<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Admin translate UI — wraps the /translate API endpoint internally.
 */
class TranslateController extends Controller
{
    public string $layout = '@app/modules/admin/views/layouts/main';

    private function jsonPath(): string
    {
        return Yii::getAlias('@app') . '/../src/assets/data/prompts.json';
    }

    private function loadPrompts(): array
    {
        $path = $this->jsonPath();
        return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    public function actionIndex()
    {
        $prompts     = $this->loadPrompts();
        $preselect   = (int)Yii::$app->request->get('id', 0);
        $selected    = null;
        $flash       = '';
        $flashType   = '';

        // Build index by ID
        $byId = [];
        foreach ($prompts as $i => $p) $byId[$p['id']] = $i;

        // Handle save
        if (Yii::$app->request->isPost && Yii::$app->request->post('action') === 'save') {
            $promptId = (int)Yii::$app->request->post('prompt_id', 0);

            if (isset($byId[$promptId])) {
                $idx = $byId[$promptId];
                foreach (['prompt_tn','prompt_hi','prompt_te','prompt_kn','prompt_ml'] as $f) {
                    $v = trim(Yii::$app->request->post($f, ''));
                    if ($v !== '') $prompts[$idx][$f] = $v;
                }
                $ok = file_put_contents(
                    $this->jsonPath(),
                    json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ) !== false;

                $flash     = $ok ? "Translations saved for Prompt #$promptId." : 'Write failed. Check permissions.';
                $flashType = $ok ? 'success' : 'error';
                $preselect = $promptId;

                // Reload
                $prompts = $this->loadPrompts();
                $byId    = [];
                foreach ($prompts as $i => $p) $byId[$p['id']] = $i;
            }
        }

        if ($preselect && isset($byId[$preselect])) {
            $selected = $prompts[$byId[$preselect]];
        }

        $missing = array_values(array_filter($prompts, fn($p) =>
            empty($p['prompt_tn']) || empty($p['prompt_hi']) ||
            empty($p['prompt_te']) || empty($p['prompt_kn']) || empty($p['prompt_ml'])
        ));

        return $this->render('@app/modules/admin/views/translate/index', [
            'prompts'   => $prompts,
            'selected'  => $selected,
            'missing'   => $missing,
            'flash'     => $flash,
            'flashType' => $flashType,
        ]);
    }

    /**
     * AJAX endpoint: POST /admin/translate/auto
     * Calls the /translate REST endpoint internally.
     */
    public function actionAuto(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $text   = trim(Yii::$app->request->post('prompt_text', ''));
        $apiKey = Yii::$app->params['geminiApiKey'];

        if ($text === '') return ['error' => 'prompt_text required'];
        if ($apiKey === 'YOUR_GEMINI_API_KEY') return ['error' => 'Gemini API key not set in params.php'];

        // Reuse TranslateController logic
        $ctrl = new \app\controllers\TranslateController('translate', Yii::$app);
        try {
            // Direct internal call to Gemini
            $result = $this->callGemini($text, $apiKey);
            return ['translations' => $result];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function callGemini(string $text, string $apiKey): array
    {
        $instruction = <<<PROMPT
Translate the following AI image generation prompt into Tamil, Hindi, Telugu, Kannada, and Malayalam.
Keep technical AI terms in English. Return ONLY a raw JSON object with keys: "ta", "hi", "te", "kn", "ml".

Prompt:
{$text}
PROMPT;

        $body = json_encode([
            'contents'         => [['parts' => [['text' => $instruction]]]],
            'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 2048],
        ]);

        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey);
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
            throw new \RuntimeException("Gemini API error (HTTP $httpCode)");
        }

        $data = json_decode($response, true);
        $raw  = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $raw  = preg_replace(['/^```json\s*/i', '/\s*```$/'], '', trim($raw));

        $t = json_decode(trim($raw), true);
        if (!is_array($t) || !isset($t['ta'])) {
            throw new \RuntimeException('Unexpected format from Gemini');
        }
        return $t;
    }
}
