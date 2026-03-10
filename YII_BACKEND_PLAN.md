# GeminiMuse — Yii2 Backend Migration Plan

> Role: System Architect
> Current backend: Plain PHP (config.php, cors.php, copy.php, favorites.php, stats.php, admin/)
> Target: Yii2 REST API with built-in validation, CORS, rate limiting, and admin CRUD

---

## Why Yii2 (not Yii3)

| Factor | Yii2 | Yii3 |
|---|---|---|
| PHP requirement | 7.4 / 8.x | 8.1+ |
| Shared hosting support | Excellent | Limited |
| Maturity | Production-proven since 2014 | Still maturing (2023+) |
| Ecosystem / docs | Huge | Growing |
| REST API support | Built-in (`yii\rest`) | Built-in |
| ActiveRecord | Yes | Yes (Cycle ORM option) |
| **Verdict** | **Use this** | Skip for now |

---

## What Yii2 Replaces vs. What We Keep

### Replaced (boilerplate we wrote manually)
| Current file | Replaced by Yii2 |
|---|---|
| `cors.php` → `handle_cors()` | `yii\filters\Cors` behavior |
| `config.php` → `get_db()` PDO | `yii\db\Connection` component |
| Manual `json_response()` / `json_error()` | Yii2 REST response formatter |
| Manual `$_POST` / `file_get_contents('php://input')` | `Yii::$app->request->getBodyParam()` |
| Manual UUID regex in every file | Single `DeviceIdValidator` model rule |
| Manual rate limit counter query in `copy.php` | `yii\filters\RateLimiter` behavior |

### Kept (no change needed)
- MySQL schema (`setup.sql`) — identical tables
- API contracts — same URL paths, same JSON request/response shapes
- Angular `ApiService` — zero changes needed on the frontend

---

## Project Structure

```
backend-yii/
├── api/
│   ├── config/
│   │   ├── web.php          # App bootstrap: modules, components, CORS origins
│   │   ├── db.php           # DB credentials (gitignored — copy from .env.example)
│   │   └── params.php       # Gemini API key, allowed origins, rate limits
│   │
│   ├── controllers/
│   │   ├── FavoriteController.php     # GET/POST /favorites
│   │   ├── CopyController.php         # POST /copy
│   │   ├── StatsController.php        # GET /stats
│   │   └── TranslateController.php    # POST /translate  (new)
│   │
│   ├── models/
│   │   ├── Favorite.php               # ActiveRecord → favorites table
│   │   ├── CopyEvent.php              # ActiveRecord → copy_events table
│   │   ├── PromptStat.php             # ActiveRecord → prompt_stats table
│   │   └── DeviceIdForm.php           # Form model with UUID validation rule
│   │
│   ├── behaviors/
│   │   └── CorsHeaderBehavior.php     # Reusable CORS + allowed origins check
│   │
│   ├── web/
│   │   ├── index.php                  # Entry point (sets DOCUMENT_ROOT)
│   │   └── .htaccess                  # Pretty URLs via mod_rewrite
│   │
│   └── runtime/                       # Yii logs + cache (gitignored)
│
├── composer.json
├── composer.lock
└── .env.example                       # Template for DB + API key config
```

---

## API Endpoints (Zero Breaking Changes)

All URLs stay the same. Angular `ApiService` needs no edits.

```
GET  /api/favorites?device_id={uuid}
     → {"device_id": "...", "favorites": [1, 3, 7]}

POST /api/favorites
     Body: {"device_id": "...", "prompt_id": 42}
     → {"action": "added|removed", "prompt_id": 42, "favorites": [...]}

POST /api/copy
     Body: {"device_id": "...", "prompt_id": 42, "language": "ta"}
     → {"prompt_id": 42, "copy_count": 183}

GET  /api/stats?id=42
     → {"prompt_id": 42, "copy_count": 183}

GET  /api/stats?ids=1,2,3
     → {"1": 42, "2": 7, "3": 0}

POST /api/translate                         ← NEW (replaces admin/translate.php AJAX)
     Body: {"prompt_text": "A cinematic portrait..."}
     → {"translations": {"ta": "...", "hi": "...", "te": "...", "kn": "...", "ml": "..."}}
```

---

## Phase 0 — Project Setup (Day 1)

### 0.1 Install Yii2 via Composer

```bash
# Install locally, then upload vendor/ to shared hosting
composer create-project --prefer-dist yiisoft/yii2-app-basic backend-yii

# Or add to existing composer.json:
composer require yiisoft/yii2
```

### 0.2 Configure `composer.json`

```json
{
  "name": "geminimuse/backend",
  "require": {
    "php": ">=7.4",
    "yiisoft/yii2": "~2.0.48"
  },
  "autoload": {
    "psr-4": { "app\\": "api/" }
  }
}
```

### 0.3 Entry Point `api/web/index.php`

```php
<?php
defined('YII_DEBUG')   or define('YII_DEBUG',   false);
defined('YII_ENV')     or define('YII_ENV',     'prod');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
(new yii\web\Application($config))->run();
```

### 0.4 URL Rewrite `api/web/.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

### 0.5 App Config `api/config/web.php`

```php
<?php
$db = require __DIR__ . '/db.php';

return [
  'id'         => 'geminimuse-api',
  'basePath'   => dirname(__DIR__),
  'bootstrap'  => ['log'],
  'aliases'    => ['@webroot' => '@app/web'],

  'components' => [
    'db'       => $db,
    'request'  => [
      'enableCsrfValidation' => false,    // REST API — no CSRF
      'parsers' => [
        'application/json' => yii\web\JsonParser::class,
      ],
    ],
    'response' => [
      'format' => yii\web\Response::FORMAT_JSON,   // Always JSON
    ],
    'log' => [
      'targets' => [[
        'class'       => 'yii\log\FileTarget',
        'levels'      => ['error', 'warning'],
      ]],
    ],
    'urlManager' => [
      'enablePrettyUrl'     => true,
      'enableStrictParsing' => true,
      'showScriptName'      => false,
      'rules' => [
        // Favorites
        'GET  favorites'  => 'favorite/index',
        'POST favorites'  => 'favorite/toggle',
        // Copy
        'POST copy'       => 'copy/record',
        // Stats
        'GET  stats'      => 'stats/index',
        // Translate
        'POST translate'  => 'translate/index',
        // OPTIONS preflight for all routes
        'OPTIONS <path:.*>' => 'site/options',
      ],
    ],
  ],

  'params' => require __DIR__ . '/params.php',
];
```

### 0.6 DB Config `api/config/db.php` (gitignored)

```php
<?php
return [
  'class'    => 'yii\db\Connection',
  'dsn'      => 'mysql:host=localhost;dbname=geminimuse;charset=utf8mb4',
  'username' => 'your_db_user',
  'password' => 'your_db_password',
  'charset'  => 'utf8mb4',
  'attributes' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
];
```

### 0.7 Params `api/config/params.php`

```php
<?php
return [
  'geminiApiKey'   => getenv('GEMINI_API_KEY') ?: 'YOUR_KEY_HERE',
  'allowedOrigins' => [
    'https://codingtamilan.in',
    'http://localhost:4200',
    'capacitor://localhost',
    'http://localhost',
  ],
  'copyRateLimitPerDay' => 20,
];
```

---

## Phase 1 — Core API Controllers (Days 2–4)

### 1.1 Base Controller with CORS

All API controllers extend this. CORS is handled once here.

```php
// api/controllers/BaseApiController.php
class BaseApiController extends yii\rest\Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Remove Yii's default auth — we use device IDs
        unset($behaviors['authenticator']);

        // Add CORS
        $behaviors['corsFilter'] = [
            'class'  => \yii\filters\Cors::class,
            'cors'   => [
                'Origin'                           => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method'    => ['GET', 'POST', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers'   => ['Content-Type', 'X-Device-ID'],
                'Access-Control-Max-Age'           => 86400,
            ],
        ];

        // JSON content negotiation
        $behaviors['contentNegotiator'] = [
            'class'   => \yii\filters\ContentNegotiator::class,
            'formats' => ['application/json' => \yii\web\Response::FORMAT_JSON],
        ];

        return $behaviors;
    }

    // OPTIONS preflight — return 204
    public function actionOptions(): \yii\web\Response
    {
        Yii::$app->response->statusCode = 204;
        return Yii::$app->response;
    }
}
```

### 1.2 DeviceId Validation (shared model)

```php
// api/models/DeviceIdForm.php
class DeviceIdForm extends \yii\base\Model
{
    public string $device_id = '';

    public function rules(): array
    {
        return [[
            'device_id', 'match',
            'pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            'message' => 'Invalid device_id format',
        ]];
    }
}
```

### 1.3 FavoriteController

```php
// api/controllers/FavoriteController.php
class FavoriteController extends BaseApiController
{
    // GET /favorites?device_id=xxx
    public function actionIndex(): array
    {
        $deviceId = Yii::$app->request->get('device_id', '');
        $this->validateDeviceId($deviceId);

        $ids = Favorite::find()
            ->where(['device_id' => $deviceId])
            ->orderBy(['created_at' => SORT_DESC])
            ->column('prompt_id');   // returns int[]

        return ['device_id' => $deviceId, 'favorites' => $ids];
    }

    // POST /favorites  {device_id, prompt_id}
    public function actionToggle(): array
    {
        $body      = Yii::$app->request->getBodyParams();
        $deviceId  = $body['device_id'] ?? '';
        $promptId  = (int)($body['prompt_id'] ?? 0);

        $this->validateDeviceId($deviceId);
        if ($promptId <= 0) throw new \yii\web\BadRequestHttpException('Invalid prompt_id');

        $existing = Favorite::findOne(['device_id' => $deviceId, 'prompt_id' => $promptId]);

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            $fav = new Favorite(['device_id' => $deviceId, 'prompt_id' => $promptId]);
            $fav->save();
            $action = 'added';
        }

        $ids = Favorite::find()
            ->where(['device_id' => $deviceId])
            ->orderBy(['created_at' => SORT_DESC])
            ->column('prompt_id');

        return ['action' => $action, 'prompt_id' => $promptId, 'favorites' => $ids];
    }

    private function validateDeviceId(string $id): void
    {
        $form = new DeviceIdForm(['device_id' => $id]);
        if (!$form->validate()) {
            throw new \yii\web\BadRequestHttpException('Invalid device_id');
        }
    }
}
```

### 1.4 CopyController with Rate Limiting

```php
// api/controllers/CopyController.php
class CopyController extends BaseApiController
{
    // POST /copy  {device_id, prompt_id, language}
    public function actionRecord(): array
    {
        $body      = Yii::$app->request->getBodyParams();
        $deviceId  = $body['device_id'] ?? '';
        $promptId  = (int)($body['prompt_id'] ?? 0);
        $language  = preg_replace('/[^a-z]/', '', strtolower($body['language'] ?? 'en'));

        // Validate
        $form = new DeviceIdForm(['device_id' => $deviceId]);
        if (!$form->validate()) throw new \yii\web\BadRequestHttpException('Invalid device_id');
        if ($promptId <= 0)     throw new \yii\web\BadRequestHttpException('Invalid prompt_id');
        if (!$language)         $language = 'en';

        // Rate limit: 20 copies per device per prompt per day
        $limit = Yii::$app->params['copyRateLimitPerDay'];
        $todayCount = CopyEvent::find()
            ->where(['device_id' => $deviceId, 'prompt_id' => $promptId])
            ->andWhere(['>=', 'created_at', date('Y-m-d 00:00:00')])
            ->count();

        $stat = PromptStat::findOne(['prompt_id' => $promptId]);

        if ((int)$todayCount >= $limit) {
            return [
                'prompt_id'    => $promptId,
                'copy_count'   => $stat ? (int)$stat->copy_count : 0,
                'rate_limited' => true,
            ];
        }

        // Record event
        (new CopyEvent(['device_id' => $deviceId, 'prompt_id' => $promptId, 'language' => $language]))->save();

        // Upsert stat
        if (!$stat) {
            $stat = new PromptStat(['prompt_id' => $promptId, 'copy_count' => 1]);
        } else {
            $stat->copy_count++;
        }
        $stat->save();

        return ['prompt_id' => $promptId, 'copy_count' => (int)$stat->copy_count];
    }
}
```

### 1.5 StatsController

```php
// api/controllers/StatsController.php
class StatsController extends BaseApiController
{
    public function actionIndex(): array
    {
        $request = Yii::$app->request;

        // Single: GET /stats?id=42
        if ($id = $request->get('id')) {
            $promptId = (int)$id;
            if ($promptId <= 0) throw new \yii\web\BadRequestHttpException('Invalid id');
            $stat = PromptStat::findOne(['prompt_id' => $promptId]);
            return ['prompt_id' => $promptId, 'copy_count' => $stat ? (int)$stat->copy_count : 0];
        }

        // Bulk: GET /stats?ids=1,2,3
        if ($ids = $request->get('ids')) {
            $ids = array_unique(array_slice(
                array_filter(array_map('intval', explode(',', $ids)), fn($id) => $id > 0),
                0, 200
            ));
            if (empty($ids)) return (object)[];

            $rows = PromptStat::find()->where(['prompt_id' => $ids])->all();
            $map  = array_fill_keys($ids, 0);
            foreach ($rows as $row) $map[(int)$row->prompt_id] = (int)$row->copy_count;
            return $map;
        }

        throw new \yii\web\BadRequestHttpException('Provide ?id= or ?ids=');
    }
}
```

### 1.6 ActiveRecord Models

```php
// api/models/Favorite.php
class Favorite extends \yii\db\ActiveRecord
{
    public static function tableName(): string { return 'favorites'; }
    public function rules(): array {
        return [
            [['device_id', 'prompt_id'], 'required'],
            ['prompt_id', 'integer', 'min' => 1],
        ];
    }
}

// api/models/CopyEvent.php
class CopyEvent extends \yii\db\ActiveRecord
{
    public static function tableName(): string { return 'copy_events'; }
}

// api/models/PromptStat.php
class PromptStat extends \yii\db\ActiveRecord
{
    public static function tableName(): string { return 'prompt_stats'; }
}
```

---

## Phase 2 — Translate Controller (Day 5)

The Gemini API call moves from `admin/translate.php` AJAX hack into a proper controller.
Angular can now call it directly (no need to go through the admin panel).

```php
// api/controllers/TranslateController.php
class TranslateController extends BaseApiController
{
    // POST /translate  {prompt_text: "..."}
    public function actionIndex(): array
    {
        $text = trim(Yii::$app->request->getBodyParam('prompt_text', ''));
        if ($text === '') throw new \yii\web\BadRequestHttpException('prompt_text is required');

        $apiKey = Yii::$app->params['geminiApiKey'];
        if ($apiKey === 'YOUR_KEY_HERE') {
            throw new \yii\web\ServerErrorHttpException('Gemini API key not configured');
        }

        $instruction = <<<PROMPT
Translate the following AI image generation prompt into Tamil, Hindi, Telugu, Kannada, and Malayalam.
Keep technical AI terms (cinematic, bokeh, 8K, hyper-realistic) in English.
Return ONLY a valid JSON object with keys: "ta", "hi", "te", "kn", "ml".

Prompt:
{$text}
PROMPT;

        $body = json_encode([
            'contents'         => [['parts' => [['text' => $instruction]]]],
            'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 2048],
        ]);

        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode !== 200) {
            throw new \yii\web\ServerErrorHttpException("Gemini API error (HTTP $httpCode)");
        }

        $data = json_decode($response, true);
        $raw  = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $raw  = preg_replace(['/^```json\s*/i', '/\s*```$/'], '', trim($raw));

        $translations = json_decode($raw, true);
        if (!is_array($translations) || !isset($translations['ta'])) {
            throw new \yii\web\ServerErrorHttpException('Unexpected Gemini response format');
        }

        return ['translations' => $translations];
    }
}
```

---

## Phase 3 — Admin Panel Migration (Days 6–8)

Migrate `backend/admin/` from plain PHP to a Yii2 module with GridView.

### Admin Module Structure

```
api/modules/admin/
├── AdminModule.php
├── controllers/
│   ├── DefaultController.php     # Dashboard (prompt list + stats)
│   ├── PromptController.php      # Add / edit prompt in JSON
│   └── TranslateController.php   # Auto-translate UI
├── models/
│   └── PromptSearch.php          # SearchModel for GridView filtering
└── views/
    ├── layouts/main.php           # Admin layout (nav + session flash)
    ├── default/index.php          # GridView dashboard
    ├── prompt/add.php             # Add prompt form
    └── translate/index.php        # Translation UI
```

### Key Yii2 Admin Features Gained

| Feature | Current (plain PHP) | Yii2 Admin |
|---|---|---|
| Prompt table | Manual HTML `<table>` | `GridView` with auto-sort + pagination |
| Search / filter | Manual `array_filter` | `SearchModel` + `DataProvider` |
| Form validation | Manual `if (!$field)` | `Model::rules()` + AJAX validation |
| Flash messages | Manual `$_SESSION` | `Yii::$app->session->setFlash()` |
| CSRF protection | None | Enabled by default |
| Error pages | PHP die() | Yii2 error handler views |

### Accessing the Admin

URL: `https://codingtamilan.in/gemini-muse/api/admin`

Protected by Yii2 session-based login (same password approach, just cleaner):

```php
// AdminModule.php
public function behaviors(): array {
    return ['access' => [
        'class' => \yii\filters\AccessControl::class,
        'rules' => [['allow' => true, 'roles' => ['@']]],   // logged-in only
    ]];
}
```

---

## Phase 4 — Deploy to Shared Hosting (Day 9)

### Step-by-step

```bash
# 1. Install dependencies locally
cd backend-yii
composer install --no-dev --optimize-autoloader

# 2. Copy db.php from template, fill in real credentials
cp api/config/db.php.example api/config/db.php

# 3. Upload to shared hosting via FTP/SSH
#    Upload: api/  vendor/  composer.json  composer.lock
#    Do NOT upload: api/runtime/  api/config/db.php (keep local)

# 4. On shared hosting: set document root to api/web/
#    OR create a symlink if you can't change document root:
#    ln -s /home/user/backend-yii/api/web /home/user/public_html/gemini-muse/api

# 5. Verify mod_rewrite is enabled (check .htaccess works)
curl https://codingtamilan.in/gemini-muse/api/stats?id=1
```

### Folder mapping on shared hosting

```
public_html/
└── gemini-muse/
    ├── api/              ← Yii2 web/ folder mapped here (document root target)
    │   ├── index.php
    │   └── .htaccess
    ├── assets/           ← Angular app assets
    └── index.html        ← Angular app shell
```

### Environment variables (preferred over hardcoding)

Add to shared hosting cPanel → "Environment Variables" or `.htaccess`:

```apache
SetEnv GEMINI_API_KEY your_key_here
SetEnv DB_PASS your_db_password
```

Then in `params.php` / `db.php`:
```php
'geminiApiKey' => getenv('GEMINI_API_KEY'),
'password'     => getenv('DB_PASS'),
```

---

## Phase 5 — Future Enhancements (Post-launch)

| Feature | Yii2 Component | Notes |
|---|---|---|
| API response caching | `yii\caching\FileCache` or `DbCache` | Cache `/stats?ids=…` for 60s |
| Request logging | `yii\log\DbTarget` | Log all API calls to `api_log` table |
| API versioning | Yii2 modules (`v1/`, `v2/`) | When breaking changes are needed |
| Push notification triggers | Yii2 console command + cron | Daily prompt notification via FCM |
| Admin user management | `yii2-authclient` (Google OAuth) | Replace password with Google login |

---

## Migration Checklist

- [ ] **Phase 0** — Yii2 installed, entry point working, DB connected
- [ ] **Phase 1** — FavoriteController, CopyController, StatsController live
- [ ] **Phase 1** — All existing API contracts verified with curl tests
- [ ] **Phase 2** — TranslateController live (Angular can call `/api/translate`)
- [ ] **Phase 2** — `ApiService` in Angular updated to use `/api/translate`
- [ ] **Phase 3** — Admin module with GridView dashboard
- [ ] **Phase 3** — Admin login, add-prompt form, translate UI
- [ ] **Phase 4** — Deployed to shared hosting, mod_rewrite confirmed
- [ ] **Phase 4** — Old plain-PHP backend removed (or kept at `/api-old/` temporarily)
- [ ] **Phase 5** — Caching enabled for stats endpoint

---

## Effort Estimate

| Phase | Days | Risk |
|---|---|---|
| 0 — Setup + config | 1 | Low |
| 1 — Core controllers | 2–3 | Low (direct translation of existing logic) |
| 2 — Translate controller | 1 | Low |
| 3 — Admin panel | 2–3 | Medium (Yii2 GridView learning curve) |
| 4 — Deploy | 1 | Medium (shared hosting quirks) |
| **Total** | **~9 days** | Low–Medium |

---

*No Angular changes required for Phases 0–2. Admin panel (Phase 3) replaces `backend/admin/` entirely.*
