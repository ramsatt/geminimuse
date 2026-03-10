export const environment = {
  production: true,
  // Legacy Yii2 REST API (kept for reference during migration)
  apiUrl: 'https://codingtamilan.in/gemini-muse/api',

  // ── Firebase (production project) ────────────────────────────────
  // Fill in your Firebase project credentials from the Firebase Console:
  // Project Settings → General → Your apps → SDK setup and configuration
  firebase: {
    apiKey: 'YOUR_PROD_API_KEY',
    authDomain: 'YOUR_PROD_PROJECT_ID.firebaseapp.com',
    projectId: 'YOUR_PROD_PROJECT_ID',
    storageBucket: 'YOUR_PROD_PROJECT_ID.appspot.com',
    messagingSenderId: 'YOUR_PROD_SENDER_ID',
    appId: 'YOUR_PROD_APP_ID',
  },
};
