<?php

namespace app\controllers;

use Yii;
use yii\web\Response;

/**
 * Handles OPTIONS preflight and error responses.
 */
class SiteController extends BaseApiController
{
    /** OPTIONS /api/<anything> */
    public function actionOptions(): Response
    {
        Yii::$app->response->statusCode = 204;
        return Yii::$app->response;
    }

    /** Error handler — returns JSON error shape */
    public function actionError(): array
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            $code = $exception->getCode() ?: 500;
            Yii::$app->response->statusCode = method_exists($exception, 'statusCode')
                ? $exception->statusCode
                : 500;
            return ['error' => $exception->getMessage(), 'code' => $code];
        }
        return ['error' => 'Unknown error'];
    }
}
