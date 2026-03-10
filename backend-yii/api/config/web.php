<?php
$db     = require __DIR__ . '/db.php';
$params = require __DIR__ . '/params.php';

return [
    'id'       => 'geminimuse-api',
    'basePath' => dirname(__DIR__),
    'aliases'  => [
        '@webroot' => '@app/../web',
        '@web'     => '',
    ],
    'bootstrap' => ['log'],

    'modules' => [
        'admin' => [
            'class' => 'app\modules\admin\AdminModule',
        ],
    ],

    'components' => [

        // ── Database ─────────────────────────────────────────
        'db' => $db,

        // ── Cache (file-based, works on all shared hosting) ──
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],

        // ── Request ──────────────────────────────────────────
        'request' => [
            'enableCsrfValidation' => false,   // REST API — CSRF not needed
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],

        // ── Response — always JSON for /api routes ───────────
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
        ],

        // ── Error handler ────────────────────────────────────
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        // ── Session (used by admin panel) ────────────────────
        'session' => [
            'class'       => 'yii\web\Session',
            'name'        => 'gm_admin',
            'cookieParams' => ['httponly' => true, 'samesite' => 'Lax'],
        ],

        // ── User (admin login) ───────────────────────────────
        'user' => [
            'class'           => 'yii\web\User',
            'identityClass'   => 'app\models\AdminUser',
            'enableAutoLogin' => false,
            'loginUrl'        => ['/admin/default/login'],
        ],

        // ── URL Manager ──────────────────────────────────────
        'urlManager' => [
            'enablePrettyUrl'     => true,
            'enableStrictParsing' => true,
            'showScriptName'      => false,
            'rules' => [
                // ── REST API routes ──────────────────────────
                'GET  favorites'    => 'favorite/index',
                'POST favorites'    => 'favorite/toggle',

                'POST copy'         => 'copy/record',

                'GET  stats'        => 'stats/index',

                'POST translate'    => 'translate/index',

                // OPTIONS preflight for all API routes
                'OPTIONS <path:.*>' => 'site/options',

                // ── Admin module ─────────────────────────────
                'admin'             => 'admin/default/index',
                'admin/<controller:\w+>/<action:\w+>' => 'admin/<controller>/<action>',
                'admin/<controller:\w+>'              => 'admin/<controller>/index',
            ],
        ],

        // ── Logging ──────────────────────────────────────────
        'log' => [
            'traceLevel' => 0,
            'targets' => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],

    ], // components

    'params' => $params,
];
