# Gemini Muse - Android Build & Install Guide

## 1. Clean Previous Installation
First, uninstall any old version of the app:
```bash
adb uninstall com.geminiprompts.app
adb uninstall com.codingtamilan.geminimuse
```

## 2. Clean Build
```bash
cd android
./gradlew clean
./gradlew assembleDebug
```

## 3. Install New APK
```bash
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

## 4. Launch App (NEW PACKAGE NAME)
```bash
adb shell am start -n com.codingtamilan.geminimuse/.MainActivity -a android.intent.action.MAIN -c android.intent.category.LAUNCHER
```

## Alternative: Open in Android Studio
1. Open `android` folder in Android Studio
2. Click "Run" (green play button)
3. Select your device
4. Android Studio will handle everything automatically

## Verify Package Name
To verify the installed package:
```bash
adb shell pm list packages | grep gemini
```
Should show: `com.codingtamilan.geminimuse`

## Build Release APK (Production)
```bash
cd android
./gradlew assembleRelease
```
APK location: `android/app/build/outputs/apk/release/app-release-unsigned.apk`

---
**Important:** Replace AdMob Test IDs with your real IDs before production!
File: `src/app/services/admob/admob.service.ts`
