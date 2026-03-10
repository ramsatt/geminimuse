<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;

/**
 * Add / edit prompts in prompts.json
 */
class PromptController extends Controller
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

    private function savePrompts(array $prompts): bool
    {
        return file_put_contents(
            $this->jsonPath(),
            json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ) !== false;
    }

    // ── Add Prompt ───────────────────────────────────────────────
    public function actionAdd()
    {
        $flash    = '';
        $flashType = '';

        if (Yii::$app->request->isPost) {
            $post     = Yii::$app->request->post();
            $promptEn = trim($post['prompt'] ?? '');
            $imageUrl = trim($post['image_url'] ?? '');

            if ($promptEn === '' || $imageUrl === '') {
                $flash     = 'Prompt text and image URL are required.';
                $flashType = 'error';
            } else {
                $prompts  = $this->loadPrompts();
                $maxId    = count($prompts) > 0 ? max(array_column($prompts, 'id')) : 0;
                $newId    = $maxId + 1;
                $filename = trim($post['filename'] ?? '') ?: basename(parse_url($imageUrl, PHP_URL_PATH));

                $entry = [
                    'id'       => $newId,
                    'filename' => $filename,
                    'url'      => $imageUrl,
                    'prompt'   => $promptEn,
                ];

                foreach (['prompt_tn','prompt_hi','prompt_te','prompt_kn','prompt_ml'] as $f) {
                    $v = trim($post[$f] ?? '');
                    if ($v !== '') $entry[$f] = $v;
                }
                if (!empty($post['category'])) $entry['category'] = $post['category'];
                if (!empty($post['is_new']))   $entry['is_new']   = true;

                $prompts[] = $entry;

                if ($this->savePrompts($prompts)) {
                    $flash     = "Prompt #$newId saved. <a href='/admin/translate/index?id=$newId'>Auto-translate →</a>";
                    $flashType = 'success';
                } else {
                    $flash     = 'Failed to write prompts.json. Check file permissions.';
                    $flashType = 'error';
                }
            }
        }

        $categories = [
            'portrait','cinematic','fantasy','anime','street',
            'nature','sci-fi','architecture','food','wildlife',
            'abstract','festive','fashion','macro',
        ];

        return $this->render('@app/modules/admin/views/prompt/add', [
            'flash'      => $flash,
            'flashType'  => $flashType,
            'categories' => $categories,
            'post'       => Yii::$app->request->post(),
        ]);
    }
}
