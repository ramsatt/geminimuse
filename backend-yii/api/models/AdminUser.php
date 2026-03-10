<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\IdentityInterface;

/**
 * Simple single-admin identity using a hashed password in params.php.
 * No database table — credentials live in config only.
 *
 * To generate a password hash, run:
 *   php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
 */
class AdminUser extends Model implements IdentityInterface
{
    public const ADMIN_ID = 1;

    public int    $id       = self::ADMIN_ID;
    public string $username = 'admin';

    // ── IdentityInterface ────────────────────────────────────────

    public static function findIdentity($id): ?self
    {
        return (int)$id === self::ADMIN_ID ? new self() : null;
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return null; // not used
    }

    public static function findByUsername(string $username): ?self
    {
        return $username === 'admin' ? new self() : null;
    }

    public function validatePassword(string $password): bool
    {
        $hash = Yii::$app->params['adminPasswordHash'] ?? '';
        return $hash && password_verify($password, $hash);
    }

    public function getId(): int          { return $this->id; }
    public function getAuthKey(): string  { return ''; }
    public function validateAuthKey($key): bool { return false; }
}
