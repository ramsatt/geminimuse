# GeminiMuse — Yii2 Backend

REST API + Admin panel for GeminiMuse.

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Configure database
cp api/config/db.php.example api/config/db.php
# Edit db.php — fill in host, dbname, username, password

# 3. Set Gemini API key (in params.php or environment)
export GEMINI_API_KEY=your_key_here

# 4. Generate admin password hash
php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
# Paste result into api/config/params.php → adminPasswordHash

# 5. Run locally (point to web/ as document root)
php -S localhost:8080 -t web/
```

## API Endpoints

| Method | URL | Description |
|---|---|---|
| GET | /favorites?device_id={uuid} | Get favorite prompt IDs |
| POST | /favorites | Toggle favorite |
| POST | /copy | Record prompt copy |
| GET | /stats?id=42 | Single prompt copy count |
| GET | /stats?ids=1,2,3 | Bulk copy counts |
| POST | /translate | Auto-translate via Gemini |

## Admin Panel

`http://localhost:8080/admin`

- Dashboard with searchable prompt table + copy counts
- Add new prompts (writes to `src/assets/data/prompts.json`)
- Auto-translate prompts to 5 Indian languages via Gemini API

## Deploy to Shared Hosting

```bash
# Build without dev dependencies
composer install --no-dev --optimize-autoloader

# Upload via FTP:
#   web/          → public_html/gemini-muse/api/
#   api/          → /home/user/backend-yii/api/
#   vendor/       → /home/user/backend-yii/vendor/

# Set document root to point to web/ folder
# Ensure mod_rewrite is enabled
```
