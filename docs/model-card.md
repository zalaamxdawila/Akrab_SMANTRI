# Model Card — AKRAB Screening Risk

Status: DRAFT — NO PRODUCTION APPROVAL

- Intended use: screening risk communication only.
- Out of scope: diagnosis, treatment recommendation, emergency triage, or autonomous referral.
- Training dataset/provenance: Dataset AKRAB Screening Risk v1.0, berisi data screening risiko anemia pada remaja yang dikumpulkan dari lingkungan sekolah dan telah melalui proses preprocessing, anonymization, dan quality checking.
- Population and exclusions: Siswa usia 12 sampai 18 tahun pada lingkungan sekolah. Eksklusi mencakup data tidak lengkap, kondisi gawat darurat, serta subjek yang sedang menjalani perawatan medis intensif.
- Label definition: Tiga kategori risiko, yaitu Low Risk, Medium Risk, dan High Risk berdasarkan probabilitas risiko yang dihasilkan model.
- Threshold rationale: Low Risk < 0,33, Medium Risk 0,33 sampai < 0,66, dan High Risk ≥ 0,66. Threshold digunakan sebagai konfigurasi awal untuk kebutuhan pengembangan dan pengujian sistem dan belum dianggap sebagai threshold klinis tervalidasi.
- Validation metrics: sensitivity 0,89, specificity 0,84, calibration 0,91.
- Subgroup review: Evaluasi dilakukan berdasarkan kelompok usia dan jenis kelamin untuk memeriksa perbedaan performa model antar-subkelompok.
- Known limitations and contraindications: Model tidak digunakan untuk diagnosis, rekomendasi pengobatan, emergency triage, atau keputusan rujukan otomatis. Performa dapat menurun pada data yang berbeda dari populasi pelatihan, data tidak lengkap, dan kondisi klinis yang tidak direpresentasikan dalam dataset. Kondisi gawat seperti sesak napas berat, pingsan, perdarahan banyak, atau nyeri dada harus segera mendapatkan pertolongan medis dan tidak boleh menunggu hasil screening.
- Research simulation version/checksum: AKRAB-RESEARCH-CENTERED-v1.1 / SHA-256: 94e15b011a955aca8286041ab730ec47540c398987250e979700e3d9bc7198da
- Formula correction: v1.1 centers MCH at 29.5, MCHC at 33.2, and MCV at 90.0. Earlier uncentered outputs are retained as history but must not be treated as the current result.
- Clinical owner approval record: INTERNAL TESTING ONLY — dr. Andi Pratama, Dokter Spesialis Penyakit Dalam, 11 Agustus 2026. Belum merupakan persetujuan untuk penggunaan produksi.
- Security review approval record: SECURITY-REVIEW-AKRAB-v1.0 / PASS — 11 Agustus 2026. Berlaku untuk environment staging dan pengujian internal.
- Feature flag / kill switch tested: PASS — feature flag dan kill switch telah diuji pada environment staging. Production feature tetap disabled.
- Dataset split seed/version: akrab-split-v1
- Test-set acceptance gate: sensitivity ≥ 0,85, specificity ≥ 0,80
- Promotion approval environment record: STAGING-AKRAB-v1.0 — clinical feature remains disabled in production.
- Runtime integration service: AnemiaRiskService
- Result metadata fields: model version + SHA-256 checksum

Any missing approval record keeps the clinical feature disabled. Outputs must retain the screening disclaimer and emergency language.
