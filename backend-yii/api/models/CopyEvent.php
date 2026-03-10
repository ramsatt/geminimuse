<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * CopyEvent ActiveRecord
 *
 * @property int    $id
 * @property string $device_id
 * @property int    $prompt_id
 * @property string $language
 * @property string $created_at
 */
class CopyEvent extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'copy_events';
    }

    public function rules(): array
    {
        return [
            [['device_id', 'prompt_id'], 'required'],
            ['prompt_id', 'integer', 'min' => 1],
            ['language', 'string', 'max' => 5],
            ['language', 'default', 'value' => 'en'],
        ];
    }
}
