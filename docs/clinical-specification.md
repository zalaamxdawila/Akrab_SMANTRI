# Clinical Specification — AKRAB Screening

Status: DRAFT — NOT APPROVED FOR CLINICAL USE

## Clinical owner

- Nama: dr. Andi Pratama, Sp.PD
- Kredensial/profesi: Dokter Spesialis Penyakit Dalam
- Institusi: Puskesmas Padang Timur
- Tanggal review: 11 Agustus 2026
- Tanda tangan: Ada

## Populasi dan tujuan screening

Populasi target, batas usia, konteks sekolah, dan kriteria eksklusi harus ditetapkan oleh clinical owner. Tujuan sistem hanya membantu screening risiko dan menentukan tindak lanjut awal; sistem tidak mendiagnosis anemia dan tidak menggantikan pemeriksaan tenaga kesehatan.

## Label, threshold, dan kontraindikasi

Clinical owner wajib menyetujui definisi label, sumber label, threshold setiap kategori, missing-data policy, serta kontraindikasi. Threshold saat ini tidak boleh dianggap tervalidasi dan tidak boleh mengaktifkan feature flag secara mandiri.

## Jalur rujukan dan emergency language

Hasil risiko tinggi harus memberi jalur rujukan ke UKS/puskesmas sesuai SOP sekolah. Kondisi gawat seperti sesak napas berat, pingsan, perdarahan banyak, atau nyeri dada harus diarahkan segera ke layanan darurat; hasil aplikasi tidak boleh menunda pertolongan.

## Approval gate

Aktivasi mensyaratkan `CLINICAL_OWNER_APPROVED=true`, `CLINICAL_MODEL_APPROVED=true`, `CLINICAL_SPEC_VERSION`, `CLINICAL_MODEL_VERSION`, dan `CLINICAL_MODEL_CHECKSUM` terisi. Tanpa semuanya sistem tetap fail-closed walaupun `CLINICAL_RISK_ENABLED=true`.
