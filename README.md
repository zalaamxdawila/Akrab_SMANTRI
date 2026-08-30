# AKRAB Android

AKRAB adalah aplikasi skrining dini risiko anemia remaja berbasis gejala dan faktor risiko. Aplikasi Android memuat layanan resmi AKRAB melalui koneksi HTTPS sehingga pembaruan konten web langsung tersedia tanpa mengunduh APK baru.

## Unduh dan instal

- [Unduh APK Android v1.0.0 langsung dari GitHub](https://github.com/zalaamxdawila/Akrab_SMANTRI/raw/refs/heads/main/downloads/AKRAB-Android-v1.0.0.apk)
- [Unduh APK dari situs resmi AKRAB](https://akrab.portodq.com/downloads/AKRAB-Android-v1.0.0.apk)
- [Lihat checksum SHA-256](https://github.com/zalaamxdawila/Akrab_SMANTRI/raw/refs/heads/main/downloads/AKRAB-Android-v1.0.0.apk.sha256)

Setelah unduhan selesai, buka file APK dan konfirmasikan instalasi. Android dapat meminta izin untuk memasang aplikasi dari sumber ini dan menjalankan pemeriksaan Play Protect.

## Identitas dan verifikasi

| Properti | Nilai |
|---|---|
| Package ID | `com.portodq.akrab` |
| Versi | `1.0.0` (`versionCode 10000`) |
| Minimum Android | Android 7.0 / API 24 |
| Target SDK | API 36 |
| SHA-256 APK | `c82beefcd52f21261f88cac2568566e6c4c2fe6bfb74f3714d8212eed1d3e099` |
| SHA-256 sertifikat | `ad3b72bfba04fa598295a90ba4a4ea8b49ead2fe770a38811d6407b5974b656e` |

Verifikasi di Windows PowerShell:

```powershell
Get-FileHash .\AKRAB-Android-v1.0.0.apk -Algorithm SHA256
```

Hasil skrining AKRAB merupakan indikasi risiko, bukan diagnosis medis. Tindak lanjut dilakukan bersama UKS, Puskesmas, atau dokter sesuai saran pada hasil. Pengembangan materi melibatkan ahli medis sekolah terkait dan tidak menyatakan afiliasi dengan organisasi kesehatan internasional.

Repositori ini hanya berisi paket distribusi Android dan checksum publik.
