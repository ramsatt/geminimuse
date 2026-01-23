# CI/CD Pipeline Setup Guide for Gemini Muse

## Overview
This project uses GitHub Actions to automatically build and deploy the Android app to an FTP server on every code push.

## Prerequisites
- GitHub repository with your code
- FTP server access (host, username, password)
- GitHub account with repository admin access

## Setup Instructions

### 1. Configure GitHub Secrets

Go to your GitHub repository:
1. Click **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add the following secrets:

| Secret Name | Description | Example |
|------------|-------------|---------|
| `FTP_SERVER` | FTP server hostname | `ftp.example.com` |
| `FTP_USERNAME` | FTP username | `user@example.com` |
| `FTP_PASSWORD` | FTP password | `your-secure-password` |

### 2. FTP Server Directory Structure

The pipeline will create/use this structure on your FTP server:
```
/gemini-muse/
  └── builds/
      ├── gemini-muse-debug-20260123_120000.apk
      ├── gemini-muse-debug-20260123_150000.apk
      └── gemini-muse-release-20260123_120000.apk
```

### 3. Workflow Trigger

The workflow automatically runs when you:
- Push code to the `main` branch
- Create a pull request to `main`

### 4. Manual Trigger (Optional)

To run the workflow manually:
1. Go to **Actions** tab in GitHub
2. Click **Build and Deploy Gemini Muse**
3. Click **Run workflow**
4. Select branch and click **Run workflow**

## What the Pipeline Does

1. ✅ **Checkout code** - Pulls latest code from repository
2. ✅ **Setup environment** - Installs Node.js 20 and Java 17
3. ✅ **Install dependencies** - Runs `npm ci`
4. ✅ **Build web app** - Compiles Angular application
5. ✅ **Sync Capacitor** - Prepares Android project
6. ✅ **Build APKs** - Creates debug and release APKs
7. ✅ **Add timestamps** - Names APKs with build date/time
8. ✅ **Upload artifacts** - Stores APKs in GitHub (30 days)
9. ✅ **Deploy to FTP** - Uploads APKs to your FTP server

## Build Artifacts

### GitHub Artifacts (Temporary Storage)
- Stored for 30 days
- Accessible from the Actions run page
- Download link provided in workflow summary

### FTP Server (Permanent Storage)
- APKs uploaded to `/gemini-muse/builds/`
- Filenames include timestamp for version tracking
- Old builds are NOT automatically deleted (manual cleanup needed)

## APK Naming Convention

```
gemini-muse-{type}-{timestamp}.apk
```

Examples:
- `gemini-muse-debug-20260123_143022.apk`
- `gemini-muse-release-20260123_143022.apk`

## Build Status

Check build status:
1. Go to **Actions** tab in your repository
2. View recent workflow runs
3. Click on a run to see detailed logs
4. Download APKs from the **Artifacts** section

## Troubleshooting

### Build Fails
- Check the Actions logs for error messages
- Verify all secrets are correctly set
- Ensure `package.json` has all required dependencies

### FTP Upload Fails
- Verify FTP credentials in secrets
- Check FTP server permissions
- Ensure `/gemini-muse/builds/` directory exists or server allows directory creation

### APK Build Fails
- Check Java version (should be 17)
- Verify Android SDK licenses
- Review Gradle build logs in Actions output

## Customization

### Change FTP Directory
Edit `.github/workflows/build-deploy.yml`:
```yaml
server-dir: /your-custom-path/builds/
```

### Build Only on Tags
Change trigger to:
```yaml
on:
  push:
    tags:
      - 'v*'
```

### Add Slack Notifications
Add this step at the end:
```yaml
- name: Slack Notification
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

## Security Best Practices

1. ✅ Never commit secrets to code
2. ✅ Use GitHub Secrets for sensitive data
3. ✅ Rotate FTP passwords regularly
4. ✅ Use FTPS (FTP over SSL) if available
5. ✅ Limit FTP user permissions to specific directory

## Release Management

For production releases:
1. Create a git tag: `git tag v1.0.0`
2. Push tag: `git push origin v1.0.0`
3. Build will trigger automatically
4. Sign APK manually or use automated signing (requires keystore setup)

## Cost

GitHub Actions is FREE for public repositories with these limits:
- 2,000 minutes/month for private repos (free tier)
- Unlimited for public repos

Typical build time: ~5-10 minutes

---

**Need Help?** Check GitHub Actions logs or contact DevOps team.
