<?php

namespace app\modules\admin;

use Yii;
use yii\filters\AccessControl;

/**
 * Admin module — password-protected UI for managing prompts.
 * Access: /admin
 */
class AdminModule extends \yii\base\Module
{
    public string $defaultRoute = 'default/index';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // Allow login page without auth
                    [
                        'actions' => ['default/login', 'default/logout'],
                        'allow'   => true,
                    ],
                    // All other admin actions require login
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function () {
                    return Yii::$app->response->redirect(['/admin/default/login']);
                },
            ],
        ];
    }

    public function init(): void
    {
        parent::init();
        // Switch response format to HTML for admin views
        Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
    }
}
