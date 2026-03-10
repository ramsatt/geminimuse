<?php

namespace app\controllers;

use Yii;
use app\models\PromptStat;
use yii\web\BadRequestHttpException;

/**
 * Prompt Stats API
 *
 * GET /stats?id=42         → {prompt_id: 42, copy_count: 183}
 * GET /stats?ids=1,2,3     → {"1": 42, "2": 7, "3": 0}
 */
class StatsController extends BaseApiController
{
    public function actionIndex(): array
    {
        $request = Yii::$app->request;

        // ── Single ───────────────────────────────────────────────
        if ($id = $request->get('id')) {
            $promptId = (int)$id;
            if ($promptId <= 0) throw new BadRequestHttpException('Invalid id');

            $stat = PromptStat::findOne(['prompt_id' => $promptId]);
            return [
                'prompt_id'  => $promptId,
                'copy_count' => $stat ? (int)$stat->copy_count : 0,
            ];
        }

        // ── Bulk ─────────────────────────────────────────────────
        if ($ids = $request->get('ids')) {
            $ids = array_unique(
                array_slice(
                    array_filter(array_map('intval', explode(',', $ids)), fn($id) => $id > 0),
                    0, 200
                )
            );

            if (empty($ids)) return [];

            $rows = PromptStat::find()->where(['prompt_id' => $ids])->all();
            $map  = array_fill_keys($ids, 0);
            foreach ($rows as $row) {
                $map[(int)$row->prompt_id] = (int)$row->copy_count;
            }
            return $map;
        }

        throw new BadRequestHttpException('Provide ?id= or ?ids=');
    }
}
