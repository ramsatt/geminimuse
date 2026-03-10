<?php

namespace app\models;

use yii\base\Model;

/**
 * Reusable form model for validating UUID v4 device IDs.
 * Used by FavoriteController and CopyController.
 */
class DeviceIdForm extends Model
{
    public string $device_id = '';

    public function rules(): array
    {
        return [
            ['device_id', 'required', 'message' => 'device_id is required'],
            ['device_id', 'match',
                'pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                'message'  => 'Invalid device_id — must be a UUID v4',
            ],
        ];
    }
}
