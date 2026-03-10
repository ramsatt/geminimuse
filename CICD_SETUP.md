# CI/CD Pipeline Setup Guide for Gemini Muse Web App

## Overview
This project uses GitHub Actions to automatically build and deploy the web application to an FTP server on every code push.

## Prerequisites
- GitHub repository with your code
- FTP server with web hosting (cPanel, DirectAdmin, etc.)
- GitHub account with repository admin access

## Setup Instructions tet

### 1. Configure GitHub Secrets

Go to your GitHub repository:
1. Click **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add the following secrets:

| Secret Name | Description | Example |
|------------|-------------|---------|
| `FTP_SERVER` | FTP server hostname | `ftp.yourdomain.com` or `example.com` |
| `FTP_USERNAME` | FTP username | `user@yourdomain.com` |
| `FTP_PASSWORD` | FTP password | `your-secure-password` |

### 2. FTP Server Directory Structure

The pipeline will deploy to this structure on your FTP server:
```
/public_html/
  └── gemini-muse/
      ├── index.html
      ├── main-*.js
      ├── styles-*.css
      ├── assets/
      │   ├── images/
      │   └── data/
      └── deployment-info.txt
```

**Note:** Adjust `server-dir` in the workflow if your hosting uses a different public directory:
- cPanel: `/public_html/`
- Some hosts: `/htdocs/`, `/www/`, or `/html/`

### 3. Workflow Trigger

The workflow automatically runs when you:
- Push code to the `main` branch
- Create a pull request to `main`

### 4. Access Your Deployed App

After successful deployment, access your app at:
```
https://yourdomain.com/gemini-muse/
```

Or if deployed to root:
```
https://yourdomain.com/
```

## What the Pipeline Does

1. ✅ **Checkout code** - Pulls latest code from repository
2. ✅ **Setup Node.js** - Installs Node.js 20
3. ✅ **Install dependencies** - Runs `npm ci`
4. ✅ **Build web app** - Compiles Angular application to production
5. ✅ **Create archive** - Zips build for backup
6. ✅ **Upload artifact** - Stores build in GitHub (30 days)
7. ✅ **Deploy to FTP** - Uploads all files to web server
8. ✅ **Add deployment info** - Creates metadata file with build details

## Build Output

### GitHub Artifacts (Temporary Storage)
- Stored as `gemini-muse-build-{timestamp}.zip`
- Kept for 30 days
- Accessible from the Actions run page
- Useful for rollbacks or local testing

### FTP Server (Live Deployment)
- All compiled files deployed to `/public_html/gemini-muse/`
- Includes `deployment-info.txt` with build metadata
- Previous files are overwritten (incremental deployment)

## Deployment Info

Each deployment creates a `deployment-info.txt` file containing:
```
Build Date: Thu Jan 23 11:53:00 UTC 2026
Commit: abc123def456...
Branch: main
Build Number: 42
```

Access it at: `https://yourdomain.com/gemini-muse/deployment-info.txt`

## Build Status

Check build status:
1. Go to **Actions** tab in your repository
2. View recent workflow runs
3. Click on a run to see detailed logs
4. Download build zip from the **Artifacts** section

## Troubleshooting

### Build Fails
- Check the Actions logs for specific error messages
- Verify `package.json` dependencies
- Ensure `npm run build` works locally

### FTP Upload Fails
- Verify FTP credentials in GitHub secrets
- Check FTP server permissions (775 or 755 for directories)
- Ensure `/public_html/gemini-muse/` directory exists or server allows creation
- Try using IP address instead of hostname for `FTP_SERVER`

### Website Shows Errors
- Check browser console for errors
- Verify base href in `index.html` matches deployment path
- Ensure all assets are uploaded (check FTP logs in Actions)

### AdMob Not Working
- AdMob requires HTTPS for web apps
- Ensure your domain has SSL certificate
- Check browser console for Content Security Policy errors

## Customization

### Change Deployment Directory
Edit `.github/workflows/build-deploy.yml`:
```yaml
server-dir: /public_html/  # Deploy to root
# or
server-dir: /public_html/my-app/  # Custom subdirectory
```

### Deploy Only on Release Tags
Change trigger to:
```yaml
on:
  push:
    tags:
      - 'v*'
```

### Add Custom Domain
After deployment, configure your domain:
1. Point domain to server IP
2. Update `.env` files if needed
3. Configure SSL certificate

## Performance Optimization

The build is production-optimized with:
- ✅ Minified JavaScript and CSS
- ✅ Tree-shaking (removes unused code)
- ✅ Lazy loading for routes
- ✅ Asset optimization

## Security Best Practices

1. ✅ Never commit secrets to code
2. ✅ Use GitHub Secrets for FTP credentials
3. ✅ Enable HTTPS on your domain
4. ✅ Use strong FTP passwords
5. ✅ Limit FTP user permissions to web directory only
6. ✅ Consider SFTP instead of FTP if available

## Rollback Procedure

To rollback to a previous version:
1. Go to Actions → Find successful previous build
2. Download the artifact zip
3. Extract and manually upload to FTP
4. Or: Revert commit and push to trigger new deployment

## Cost

GitHub Actions is FREE for public repositories with these limits:
- 2,000 minutes/month for private repos (free tier)
- Unlimited for public repos

Typical build time: ~2-3 minutes

## Advanced: Custom Build Script

Add to `package.json`:
```json
"scripts": {
  "build:prod": "ng build --configuration production --base-href /gemini-muse/"
}
```

Update workflow:
```yaml
- name: Build Angular web app
  run: npm run build:prod
```

---

**Live URL:** https://your-domain.com/gemini-muse/
**Need Help?** Check GitHub Actions logs or contact support.

---

# CI/CD Pipeline — Yii2 Backend API

## Overview

A separate workflow (`.github/workflows/backend-yii-deploy.yml`) handles the Yii2 PHP backend. It only triggers when files inside `backend-yii/` change, keeping it independent from frontend deployments.

## Workflow File

`.github/workflows/backend-yii-deploy.yml`

## Triggers

| Event | Jobs Triggered |
|-------|---------------|
| Push to `main` (files in `backend-yii/**`) | Lint ✅ + Deploy 🚀 |
| Pull Request to `main` (files in `backend-yii/**`) | Lint ✅ only |
| Manual (`workflow_dispatch`) | Lint ✅ + Deploy 🚀 |

## Jobs

### 1. `lint` — PHP Lint & Composer Validate
- Sets up PHP 8.2 with required extensions
- Runs `composer validate --strict`
- Installs production Composer dependencies
- Runs `php -l` syntax check across all `.php` files in `api/` and `web/`

### 2. `deploy` — Deploy to FTP Server
- Only runs after `lint` passes and only on `push` to `main`
- Installs Composer dependencies (no dev packages, with autoloader optimization)
- Generates `api/config/db.php` from GitHub Secrets (never stored in repo)
- Creates a timestamped backup `.zip` (uploaded as GitHub artifact, 30-day retention)
- Deploys via `lftp` to `/backend-yii/` on the FTP server
- Writes a `deployment-info.txt` with build metadata

## Required GitHub Secrets

Add these in **Settings → Secrets and variables → Actions**:

| Secret Name | Used For | Example |
|------------|----------|---------|
| `FTP_SERVER` | FTP hostname (shared with frontend) | `ftp.yourdomain.com` |
| `FTP_USERNAME` | FTP login (shared with frontend) | `user@yourdomain.com` |
| `FTP_PASSWORD` | FTP password (shared with frontend) | `your-secure-password` |
| `YII_DB_DSN` | MySQL connection string | `mysql:host=localhost;dbname=geminimuse;charset=utf8mb4` |
| `YII_DB_USER` | Database username | `db_user` |
| `YII_DB_PASSWORD` | Database password | `db_password` |

> **Note:** `FTP_SERVER`, `FTP_USERNAME`, and `FTP_PASSWORD` are shared with the frontend workflow — no duplication needed if already set.

## FTP Server Directory Structure

```
/backend-yii/
  ├── api/
  │   ├── config/
  │   │   ├── db.php          ← generated from secrets (never in repo)
  │   │   ├── params.php
  │   │   └── web.php
  │   ├── controllers/
  │   ├── models/
  │   └── modules/
  ├── vendor/                 ← deployed separately (parallel upload)
  ├── web/
  │   ├── index.php
  │   └── .htaccess
  └── deployment-info.txt     ← written each deploy
```

## What's Excluded from Deployment

- `*.log` files
- `.env*` files
- `api/runtime/` (generated at runtime)
- `web/assets/` (generated by Yii asset manager)
- `*.example` files (template files like `db.php.example`)

## Troubleshooting

### Composer install fails
- PHP version mismatch — `composer.json` requires `>=7.4`, workflow uses `8.2` (compatible)
- Check `composer.json` for any missing extensions in the `require` section

### `db.php` not created correctly
- Verify `YII_DB_DSN`, `YII_DB_USER`, `YII_DB_PASSWORD` secrets are set correctly
- DSN format: `mysql:host=HOSTNAME;dbname=DBNAME;charset=utf8mb4`

### FTP deploy fails
- Same troubleshooting as frontend (check FTP credentials, server permissions)
- Ensure `/backend-yii/` directory exists on server or FTP user has permission to create it

### Changes not picked up
- The workflow only triggers on changes in `backend-yii/**` — if you changed something outside that path, trigger manually via `workflow_dispatch`

---

**Backend API Base URL:** https://your-domain.com/backend-yii/
**Deployment Info:** https://your-domain.com/backend-yii/deployment-info.txt
