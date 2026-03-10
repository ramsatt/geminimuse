<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use app\models\AdminUser;
use app\models\PromptStat;

/**
 * Admin dashboard + login/logout
 */
class DefaultController extends Controller
{
    public string $layout = '@app/modules/admin/views/layouts/main';

    // ── Login ────────────────────────────────────────────────────
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default/index']);
        }

        $error = '';
        if (Yii::$app->request->isPost) {
            $password = Yii::$app->request->post('password', '');
            $user     = AdminUser::findByUsername('admin');

            if ($user && $user->validatePassword($password)) {
                Yii::$app->user->login($user);
                return $this->redirect(['/admin/default/index']);
            }
            $error = 'Invalid password.';
        }

        return $this->render('@app/modules/admin/views/default/login', ['error' => $error]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['/admin/default/login']);
    }

    // ── Dashboard ────────────────────────────────────────────────
    public function actionIndex()
    {
        $jsonPath = Yii::getAlias('@app') . '/../src/assets/data/prompts.json';
        $prompts  = file_exists($jsonPath)
            ? json_decode(file_get_contents($jsonPath), true)
            : [];

        // Copy counts from DB
        $counts = [];
        try {
            $rows = PromptStat::find()->all();
            foreach ($rows as $r) $counts[$r->prompt_id] = (int)$r->copy_count;
        } catch (\Exception $e) {}

        $q   = trim(Yii::$app->request->get('q', ''));
        $cat = Yii::$app->request->get('cat', '');

        $filtered = $prompts;
        if ($q !== '') {
            $filtered = array_filter($filtered, fn($p) =>
                stripos($p['prompt'], $q) !== false || stripos((string)$p['id'], $q) !== false
            );
        }
        if ($cat !== '') {
            $filtered = array_filter($filtered, fn($p) => ($p['category'] ?? '') === $cat);
        }

        $cats = array_unique(array_filter(array_column($prompts, 'category')));
        sort($cats);

        return $this->render('@app/modules/admin/views/default/index', [
            'prompts'  => array_values($filtered),
            'allCount' => count($prompts),
            'counts'   => $counts,
            'cats'     => $cats,
            'q'        => $q,
            'cat'      => $cat,
        ]);
    }
}
