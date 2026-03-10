<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * PromptStat ActiveRecord
 *
 * @property int $prompt_id
 * @property int $copy_count
 */
class PromptStat extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'prompt_stats';
    }

    public function rules(): array
    {
        return [
            ['prompt_id',  'required'],
            ['prompt_id',  'integer', 'min' => 1],
            ['copy_count', 'integer', 'min' => 0],
            ['copy_count', 'default', 'value' => 0],
        ];
    }

    /**
     * Atomically increment the copy count for a prompt, inserting if not exists.
     */
    public static function increment(int $promptId): int
    {
        \Yii::$app->db->createCommand(
            'INSERT INTO prompt_stats (prompt_id, copy_count)
             VALUES (:id, 1)
             ON DUPLICATE KEY UPDATE copy_count = copy_count + 1',
            [':id' => $promptId]
        )->execute();

        $row = static::findOne(['prompt_id' => $promptId]);
        return $row ? (int) $row->copy_count : 1;
    }
}
