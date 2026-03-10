<?php

namespace app\controllers;

use Yii;
use app\models\CopyEvent;
use app\models\PromptStat;
use app\models\DeviceIdForm;
use yii\web\BadRequestHttpException;

/**
 * Copy Tracking API
 *
 * POST /copy {device_id, prompt_id, language}
 *      → {prompt_id, copy_count}
 *      → {prompt_id, copy_count, rate_limited: true}  (if limit hit)
 */
class CopyController extends BaseApiController
{
    public function actionRecord(): array
    {
        $body     = Yii::$app->request->getBodyParams();
        $deviceId = $body['device_id'] ?? '';
        $promptId = (int)($body['prompt_id'] ?? 0);
        $language = preg_replace('/[^a-z]/', '', strtolower($body['language'] ?? 'en'));
        $language = substr($language ?: 'en', 0, 5);

        // Validate
        $form = new DeviceIdForm(['device_id' => $deviceId]);
        if (!$form->validate()) throw new BadRequestHttpException('Invalid device_id');
        if ($promptId <= 0)     throw new BadRequestHttpException('Invalid prompt_id');

        // Rate limit: max N copies per device per prompt per calendar day
        $limit = Yii::$app->params['copyRateLimitPerDay'];

        $todayCount = CopyEvent::find()
            ->where(['device_id' => $deviceId, 'prompt_id' => $promptId])
            ->andWhere(['>=', 'created_at', date('Y-m-d 00:00:00')])
            ->count();

        if ((int)$todayCount >= $limit) {
            $stat = PromptStat::findOne(['prompt_id' => $promptId]);
            return [
                'prompt_id'    => $promptId,
                'copy_count'   => $stat ? (int)$stat->copy_count : 0,
                'rate_limited' => true,
            ];
        }

        // Record event
        $event = new CopyEvent([
            'device_id' => $deviceId,
            'prompt_id' => $promptId,
            'language'  => $language,
        ]);
        $event->save();

        // Atomically increment stat (INSERT … ON DUPLICATE KEY UPDATE)
        $newCount = PromptStat::increment($promptId);

        return [
            'prompt_id'  => $promptId,
            'copy_count' => $newCount,
        ];
    }
}
