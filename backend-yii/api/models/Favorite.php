<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Favorite ActiveRecord
 *
 * @property int    $id
 * @property string $device_id
 * @property int    $prompt_id
 * @property string $created_at
 */
class Favorite extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'favorites';
    }

    public function rules(): array
    {
        return [
            [['device_id', 'prompt_id'], 'required'],
            ['device_id', 'match',
                'pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                'message' => 'Invalid device_id format',
            ],
            ['prompt_id', 'integer', 'min' => 1],
        ];
    }
}
