export const environment = {
  production: false,
  // Legacy Yii2 REST API (kept for reference during migration)
  apiUrl: 'http://localhost:8080/api',

  // ── Firebase (dev project) ────────────────────────────────────────
  // Fill in your Firebase project credentials from the Firebase Console:
  // Project Settings → General → Your apps → SDK setup and configuration
  firebase: {
    apiKey: 'YOUR_DEV_API_KEY',
    authDomain: 'YOUR_DEV_PROJECT_ID.firebaseapp.com',
    projectId: 'YOUR_DEV_PROJECT_ID',
    storageBucket: 'YOUR_DEV_PROJECT_ID.appspot.com',
    messagingSenderId: 'YOUR_DEV_SENDER_ID',
    appId: 'YOUR_DEV_APP_ID',
  },
};
