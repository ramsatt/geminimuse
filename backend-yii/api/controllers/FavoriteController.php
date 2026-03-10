<?php

namespace app\controllers;

use Yii;
use app\models\Favorite;
use app\models\DeviceIdForm;
use yii\web\BadRequestHttpException;

/**
 * Favorites API
 *
 * GET  /favorites?device_id={uuid}            → {device_id, favorites: [1,3,7]}
 * POST /favorites {device_id, prompt_id}      → {action, prompt_id, favorites: [...]}
 */
class FavoriteController extends BaseApiController
{
    /** GET /favorites */
    public function actionIndex(): array
    {
        $deviceId = Yii::$app->request->get('device_id', '');
        $this->assertDeviceId($deviceId);

        return [
            'device_id' => $deviceId,
            'favorites' => $this->getFavoriteIds($deviceId),
        ];
    }

    /** POST /favorites */
    public function actionToggle(): array
    {
        $body      = Yii::$app->request->getBodyParams();
        $deviceId  = $body['device_id'] ?? '';
        $promptId  = (int)($body['prompt_id'] ?? 0);

        $this->assertDeviceId($deviceId);
        if ($promptId <= 0) {
            throw new BadRequestHttpException('Invalid prompt_id');
        }

        $existing = Favorite::findOne(['device_id' => $deviceId, 'prompt_id' => $promptId]);

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            $fav = new Favorite(['device_id' => $deviceId, 'prompt_id' => $promptId]);
            if (!$fav->save()) {
                throw new \yii\web\ServerErrorHttpException('Could not save favorite');
            }
            $action = 'added';
        }

        return [
            'action'    => $action,
            'prompt_id' => $promptId,
            'favorites' => $this->getFavoriteIds($deviceId),
        ];
    }

    private function getFavoriteIds(string $deviceId): array
    {
        return array_map(
            'intval',
            Favorite::find()
                ->select('prompt_id')
                ->where(['device_id' => $deviceId])
                ->orderBy(['created_at' => SORT_DESC])
                ->column()
        );
    }

    private function assertDeviceId(string $id): void
    {
        $form = new DeviceIdForm(['device_id' => $id]);
        if (!$form->validate()) {
            throw new BadRequestHttpException('Invalid device_id');
        }
    }
}
