<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\ContentNegotiator;
use yii\web\Response;

/**
 * All REST API controllers extend this.
 * Provides: CORS, JSON content negotiation, OPTIONS preflight.
 */
class BaseApiController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Remove Yii's default HTTP auth — we use device IDs
        unset($behaviors['authenticator']);

        // CORS — allowedOrigins loaded from params
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors'  => [
                'Origin'                        => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers'=> ['Content-Type', 'X-Device-ID'],
                'Access-Control-Max-Age'        => 86400,
            ],
        ];

        // Always respond with JSON
        $behaviors['contentNegotiator'] = [
            'class'   => ContentNegotiator::class,
            'formats' => ['application/json' => Response::FORMAT_JSON],
        ];

        return $behaviors;
    }

    /**
     * Handle OPTIONS preflight — return 204 No Content.
     * Registered in urlManager as: 'OPTIONS <path:.*>' => 'site/options'
     * But each controller can also expose it directly.
     */
    public function actionOptions(): Response
    {
        Yii::$app->response->statusCode = 204;
        return Yii::$app->response;
    }
}
