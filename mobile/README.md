# AKRAB Android

APK ini adalah wrapper Cordova tipis untuk `https://akrab.portodq.com/`. Konten aplikasi berasal dari website produksi, sehingga pembaruan web langsung terlihat tanpa membangun ulang APK.

## Keamanan

- Navigasi WebView dibatasi ke origin HTTPS AKRAB.
- HTTP cleartext, backup aplikasi, mode file tidak aman, dan jendela baru dinonaktifkan.
- Tidak ada plugin Cordova atau jembatan native tambahan.
- Build release wajib ditandatangani dan diverifikasi dengan `apksigner`.
- Berkas signing lokal berada di `.signing/` dan tidak boleh diunggah atau dimasukkan ke Git.
- Folder `.signing/` wajib dicadangkan secara aman. Semua versi APK berikutnya harus memakai identitas signing yang sama agar Android menerima pembaruan.

## Toolchain

- Cordova CLI 13.0.0
- cordova-android 15.1.0
- Android SDK / Build Tools 36
- JDK 17

`npm run android:release` menghasilkan APK rilis unsigned. Setelah itu APK harus di-zipalign, ditandatangani dengan identitas di `.signing/`, dan diverifikasi sebelum dipublikasikan. `npm run android:bundle` tersedia jika kelak diperlukan AAB untuk distribusi melalui Google Play.

Dokumentasi acuan:

- https://cordova.apache.org/docs/en/latest/guide/platforms/android/
- https://cordova.apache.org/docs/en/latest/guide/appdev/allowlist/
- https://cordova.apache.org/docs/en/latest/config_ref/
- https://developer.android.com/studio/publish/app-signing
- https://developer.android.com/tools/apksigner
