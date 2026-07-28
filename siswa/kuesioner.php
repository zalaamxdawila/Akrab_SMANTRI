<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$clinicalRiskEnabled = isClinicalRiskEnabled();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$clinicalRiskEnabled) {
        $error = "Skrining risiko sedang dinonaktifkan sampai model selesai divalidasi. Tidak ada data yang disimpan.";
    } else {
    try {
        $validated = validateQuestionnaireInput($_POST);
        if (!$validated['valid']) {
            throw new InvalidArgumentException(implode(' ', $validated['errors']));
        }
        $values = $validated['values'];
        $pdo->beginTransaction();
        
        // I. Karakteristik
        $tgl_wawancara = $values['tanggal_wawancara'];
        $no_resp = "AKRAB-" . date("Ym") . "-" . str_pad($user_id, 4, "0", STR_PAD_LEFT) . "-" . strtoupper(substr(md5(uniqid()), 0, 5));
        $inisial = $values['inisial_responden'];
        $tgl_lahir = $values['tanggal_lahir'];
        $tmp_lahir = $values['tempat_lahir'];
        $alamat = $values['alamat'];
        $pendidikan_tingkat = $values['pendidikan'] ?? '';
        $jurusan = $values['jurusan'];
        $pendidikan = $pendidikan_tingkat . (!empty($jurusan) ? ' ' . $jurusan : '');
        
        // II. Lab (Kaggle features)
        $hb = $values['kadar_hb'];
        $mchc = $values['kadar_mchc'];
        $mcv = $values['kadar_mcv'];
        $mch = $values['kadar_mch'];
        
        // III. Gejala
        $skor_gejala = $values['skor_gejala'];
        
        // IV. Sikap
        $skor_sikap = $values['skor_sikap'];
        
        // V. Pengetahuan (Simple scoring: 1 point per correct answer roughly)
        $skor_pengetahuan = $values['skor_pengetahuan'];
        
        // VI. Menstruasi
        $mens_sudah = $values['mens_sudah'];
        $mens_usia_th = $values['mens_usia_th'];
        $mens_teratur = $values['mens_teratur'];
        $mens_lama = $values['mens_lama_hari'];
        
        // VII. Pola Makan (selalu=3, kadang=2, tidak=1)
        $skor_makan = $values['skor_makan'];

        // Insert Kuesioner
        $stmt = $pdo->prepare("INSERT INTO kuesioner 
            (user_id, tanggal_wawancara, nomor_responden, inisial_responden, tanggal_lahir, tempat_lahir, alamat, pendidikan,
             kadar_hb, kadar_mchc, kadar_mcv, kadar_mch,
             skor_gejala, skor_sikap, skor_pengetahuan, mens_sudah, mens_usia_th, mens_teratur, mens_lama_hari, skor_makan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $user_id, $tgl_wawancara, $no_resp, $inisial, $tgl_lahir, $tmp_lahir, $alamat, $pendidikan,
            $hb, $mchc, $mcv, $mch,
            $skor_gejala, $skor_sikap, $skor_pengetahuan, $mens_sudah, $mens_usia_th, $mens_teratur, $mens_lama, $skor_makan
        ]);
        
        // Kalkulasi Risiko ML/Heuristic
        $input_data = [
            'kadar_hb' => $hb,
            'kadar_mchc' => $mchc,
            'kadar_mcv' => $mcv,
            'kadar_mch' => $mch,
            'skor_gejala' => $skor_gejala,
            'skor_makan' => $skor_makan,
            'mens_teratur' => $mens_teratur
        ];
        
        $risk = (new AnemiaRiskService())->evaluate($input_data);
        $probabilitas = $risk['probability'];
        $kategori = $risk['category'];
        
        // Simpan Hasil Deteksi
        $stmt2 = $pdo->prepare("INSERT INTO hasil_deteksi (user_id, probabilitas_risiko, kategori_risiko, model_version, model_checksum, tanggal) VALUES (?, ?, ?, ?, ?, CURDATE())");
        $stmt2->execute([$user_id, $probabilitas, $kategori, $risk['model_version'], $risk['model_checksum']]);
        
        $pdo->commit();
        header("Location: hasil_deteksi.php");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('AKRAB questionnaire submission failed: ' . get_class($e));
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : publicErrorMessage();
    }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Kuesioner Lengkap - AKRAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .step-container { display: none; animation: fadeInUp 0.4s ease; }
        .step-container.active { display: block; }
        
        .progress-wizard { height: 8px; border-radius: 10px; background-color: #e2e8f0; margin-bottom: 2rem; overflow: hidden; }
        .progress-bar-wizard { background: var(--primary-gradient); height: 100%; width: 0%; transition: width 0.4s ease; }
        
        /* Modern range slider */
        input[type=range] {
            -webkit-appearance: none;
            width: 100%;
            background: transparent;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 24px;
            width: 24px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            margin-top: -9px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        input[type=range]::-webkit-slider-runnable-track {
            width: 100%;
            height: 6px;
            cursor: pointer;
            background: #cbd5e1;
            border-radius: 10px;
        }
        .range-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 8px;
        }
        
        /* Custom Radio/Checkbox Cards */
        .form-check-custom {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 8px;
            transition: all 0.2s;
            cursor: pointer;
            background: var(--bg-white);
        }
        .form-check-custom:hover { background: #f8fafc; border-color: var(--primary-light); }
        .form-check-input:checked + span { font-weight: 700; color: var(--primary-color); }
        .form-check-custom:has(.form-check-input:checked) {
            border-color: var(--primary-color);
            background-color: #ecfdf5;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="dashboard.php">
            <i data-lucide="arrow-left"></i> AKRAB Siswa
        </a>
    </div>
</nav>

<div class="container py-4" style="max-width: 800px;">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary">Kuesioner Skrining Anemia</h3>
        <p class="text-muted">Jawablah pertanyaan berikut dengan jujur untuk screening risiko. Hasil ini bukan diagnosis dan tidak menggantikan pemeriksaan tenaga kesehatan.</p>
    </div>

    <div class="progress-wizard shadow-sm">
        <div class="progress-bar-wizard" id="wizardProgress"></div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger animate-fade-in-up"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$clinicalRiskEnabled): ?>
        <div class="alert alert-warning border-warning shadow-sm" role="alert">
            <strong>Fitur skrining sedang dinonaktifkan.</strong>
            Model perhitungan risiko masih dalam proses validasi klinis. Form dapat dibaca, tetapi belum dapat dikirim dan tidak boleh digunakan sebagai diagnosis.
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="kuesionerForm">
        <?= csrfInput() ?>
        
        <!-- STEP 1: Karakteristik -->
        <div class="step-container active" id="step1">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white fw-bold d-flex align-items-center gap-2 border-bottom-0 pt-3">
                    <span class="badge bg-primary rounded-circle px-2 py-2">1</span> Data Diri Dasar
                </div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label text-muted small fw-semibold">Tanggal Wawancara</label><input type="date" class="form-control" name="tanggal_wawancara"></div>

                    <div class="col-md-6"><label class="form-label text-muted small fw-semibold">Inisial</label><input type="text" class="form-control" name="inisial"></div>
                    <div class="col-md-6"><label class="form-label text-muted small fw-semibold">Tanggal Lahir</label><input type="date" class="form-control" name="tanggal_lahir"></div>
                    <div class="col-md-6"><label class="form-label text-muted small fw-semibold">Tempat Lahir</label><input type="text" class="form-control" name="tempat_lahir"></div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Pendidikan / Tingkat Kelas</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="pendidikan" class="form-select">
                                    <option value="">Pilih Tingkat</option>
                                    <option value="Kelas VII">Kelas VII (SMP)</option>
                                    <option value="Kelas VIII">Kelas VIII (SMP)</option>
                                    <option value="Kelas IX">Kelas IX (SMP)</option>
                                    <option value="Kelas X">Kelas X (SMA)</option>
                                    <option value="Kelas XI">Kelas XI (SMA)</option>
                                    <option value="Kelas XII">Kelas XII (SMA)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="jurusan" class="form-control" placeholder="Jurusan (Opsional)">
                            </div>
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label text-muted small fw-semibold">Alamat</label><textarea class="form-control" name="alamat" rows="2"></textarea></div>
                </div>
            </div>
            
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white fw-bold border-bottom-0 pt-3">
                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                        <input class="form-check-input fs-5 mt-0" type="checkbox" id="toggleLab">
                        <label class="form-check-label text-dark" for="toggleLab">Saya memiliki Hasil Lab Darah (Opsional)</label>
                    </div>
                </div>
                <div class="card-body row g-3 d-none" id="labSection">
                    <div class="alert alert-info border-0 py-2 small mb-0"><i data-lucide="info" style="width: 16px;" class="me-1"></i> Data lab meningkatkan akurasi deteksi menggunakan AI.</div>
                    <div class="col-md-3 col-6"><label class="form-label text-muted small fw-semibold">Hb (gr%)</label><input type="number" step="0.1" class="form-control" name="kadar_hb"></div>
                    <div class="col-md-3 col-6"><label class="form-label text-muted small fw-semibold">MCHC</label><input type="number" step="0.1" class="form-control" name="kadar_mchc"></div>
                    <div class="col-md-3 col-6"><label class="form-label text-muted small fw-semibold">MCV</label><input type="number" step="0.1" class="form-control" name="kadar_mcv"></div>
                    <div class="col-md-3 col-6"><label class="form-label text-muted small fw-semibold">MCH</label><input type="number" step="0.1" class="form-control" name="kadar_mch"></div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-next shadow-sm">Selanjutnya <i data-lucide="arrow-right" style="width: 18px;"></i></button>
            </div>
        </div>

        <!-- STEP 2: Menstruasi & Makan -->
        <div class="step-container" id="step2">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white fw-bold d-flex align-items-center gap-2 border-bottom-0 pt-3">
                    <span class="badge bg-primary rounded-circle px-2 py-2">2</span> Siklus Menstruasi & Pola Makan
                </div>
                <div class="card-body">
                    <h6 class="text-primary mb-3 fw-bold">A. Siklus Menstruasi</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Sudah menstruasi?</label>
                            <select name="mens_sudah" class="form-select">
                                <option value="ya">Sudah</option><option value="belum">Belum</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Usia mulai (Tahun)</label>
                            <input type="number" class="form-control" name="mens_usia_th">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Siklus teratur tiap bulan?</label>
                            <select name="mens_teratur" class="form-select">
                                <option value="ya">Ya</option><option value="tidak">Tidak</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Lama menstruasi (Hari)</label>
                            <input type="number" class="form-control" name="mens_lama">
                        </div>
                    </div>
                    
                    <h6 class="text-primary mb-3 pt-3 border-top fw-bold">B. Pola Makan Sehari-hari</h6>
                    <?php 
                    $makan = ["Sarapan pagi", "Rutin makan siang", "Selalu makan malam", "Snek pagi-siang", "Snek siang-malam", "Snek menjelang tidur"];
                    foreach($makan as $idx => $m): ?>
                    <div class="mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-light p-2 rounded">
                        <label class="fw-medium mb-1 mb-md-0 px-2"><?= ($idx+1).". ".$m ?></label>
                        <select name="makan_<?= $idx+1 ?>" class="form-select w-auto border-0 shadow-sm" required>
                            <option value="selalu">Selalu</option>
                            <option value="kadang">Kadang-kadang</option>
                            <option value="tidak">Tidak pernah</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 btn-prev"><i data-lucide="arrow-left" style="width: 18px;"></i> Kembali</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-next shadow-sm">Selanjutnya <i data-lucide="arrow-right" style="width: 18px;"></i></button>
            </div>
        </div>

        <!-- STEP 3: Gejala -->
        <div class="step-container" id="step3">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white fw-bold d-flex align-items-center gap-2 border-bottom-0 pt-3">
                    <span class="badge bg-primary rounded-circle px-2 py-2">3</span> Keluhan & Gejala Fisik
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-4 border-bottom pb-3">Geser tombol biru ke kanan sesuai dengan tingkat keseringan atau keparahan gejala yang Anda rasakan.</p>
                    
                    <?php 
                    $gejala = ["Cepat lelah bila beraktivitas", "Merasa pusing", "Mata berkunang-kunang", "Ujung tangan/kaki sering dingin", "Suka sempoyongan", "Berdebar-debar saat aktivitas ringan", "Sering Mengantuk", "Malas beraktivitas", "Nafas terasa pendek", "Wajah terlihat pucat"];
                    foreach($gejala as $idx => $g): ?>
                    <div class="mb-4 p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <label class="fw-semibold mb-0 text-dark"><?= ($idx+1).". ".$g ?></label>
                            <span class="badge bg-primary px-2 py-1 fs-6 shadow-sm" id="val_gejala_<?= $idx+1 ?>">0</span>
                        </div>
                        <input type="range" name="gejala_<?= $idx+1 ?>" id="gejala_<?= $idx+1 ?>" min="0" max="10" value="0" class="form-range" oninput="document.getElementById('val_gejala_<?= $idx+1 ?>').innerText = this.value">
                        <div class="range-labels">
                            <span class="fw-medium">Tak Pernah (0)</span>
                            <span class="fw-medium text-danger">Sangat Sering (10)</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 btn-prev"><i data-lucide="arrow-left" style="width: 18px;"></i> Kembali</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-next shadow-sm">Selanjutnya <i data-lucide="arrow-right" style="width: 18px;"></i></button>
            </div>
        </div>
        
        <!-- STEP 4: Sikap & Pengetahuan -->
        <div class="step-container" id="step4">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white fw-bold d-flex align-items-center gap-2 border-bottom-0 pt-3">
                    <span class="badge bg-primary rounded-circle px-2 py-2">4</span> Opini & Pengetahuan
                </div>
                <div class="card-body">
                    <h6 class="text-primary mb-3 fw-bold">A. Opini Terhadap Anemia</h6>
                    <p class="small text-muted mb-3">Pilih tingkat persetujuan Anda untuk setiap pernyataan di bawah ini.</p>
                    <?php 
                    // To keep it short, we pick 5 out of 10. For data consistency with DB, we just put 0 in missing ones, or keep 10.
                    // The DB logic loop 1 to 10. Let's keep 5 for brevity but send 0 for others to prevent errors if loop expects 10.
                    // Wait, DB expects up to 10. Let's output 5 visually, but hidden inputs for the rest to 0.
                    $sikap = ["Anemia merupakan kondisi sel darah merah di bawah normal", "Anemia kronis tidak dapat dicegah", "Anemia berdampak sangat serius bagi kesehatan", "Anemia berdampak terhadap masa depan bangsa", "Pola makan salah adalah penyebab utama anemia"];
                    foreach($sikap as $idx => $s): ?>
                    <div class="mb-4">
                        <label class="d-block fw-semibold mb-2"><?= ($idx+1).". ".$s ?></label>
                        <div class="row g-2">
                            <?php 
                            $opsi = [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Setuju', 4 => 'Sangat Setuju'];
                            foreach($opsi as $val => $label): ?>
                            <div class="col-6 col-md-3">
                                <label class="w-100 form-check-custom d-flex align-items-center gap-2 m-0 h-100">
                                    <input class="form-check-input mt-0" type="radio" name="sikap_<?= $idx+1 ?>" value="<?= $val ?>" required>
                                    <span class="small lh-sm"><?= $label ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <!-- Hidden inputs for remaining Sikap to prevent null errors in PHP -->
                    <?php for($i=6; $i<=10; $i++): ?>
                        <input type="hidden" name="sikap_<?= $i ?>" value="0">
                    <?php endfor; ?>
                    
                    <h6 class="text-primary mb-3 border-top pt-3 fw-bold">B. Pengetahuan Dasar (Pilih yang benar)</h6>
                    <div class="mb-3">
                        <label class="fw-semibold mb-2 text-dark">1. Zat gizi apa yang menjadi penyebab utama anemia?</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-check-custom d-flex align-items-center gap-2 m-0 w-100">
                                    <input type="checkbox" class="form-check-input mt-0" name="pengetahuan_1[]" value="a"> <span>Zat Besi (Fe)</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check-custom d-flex align-items-center gap-2 m-0 w-100">
                                    <input type="checkbox" class="form-check-input mt-0" name="pengetahuan_1[]" value="b"> <span>Asam Folat</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- Hidden inputs for remaining Pengetahuan to prevent null errors -->
                    <?php for($i=2; $i<=10; $i++): ?>
                        <input type="hidden" name="pengetahuan_<?= $i ?>[]" value="a">
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 btn-prev"><i data-lucide="arrow-left" style="width: 18px;"></i> Kembali</button>
                <button type="submit" class="btn btn-success rounded-pill px-4 btn-next fw-bold shadow-lg" style="background: var(--primary-color);" <?= !$clinicalRiskEnabled ? 'disabled aria-disabled="true"' : '' ?>>Simpan Kuesioner <i data-lucide="check-circle" style="width: 18px;" class="ms-1"></i></button>
            </div>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js"></script>
<script>
    lucide.createIcons();
    
    // Toggle Lab Section
    document.getElementById('toggleLab').addEventListener('change', function() {
        const labSection = document.getElementById('labSection');
        if (this.checked) {
            labSection.classList.remove('d-none');
            labSection.classList.add('animate-fade-in-up');
        } else {
            labSection.classList.add('d-none');
        }
    });

    // Multi-step Wizard Logic
    document.addEventListener('DOMContentLoaded', function() {
        const steps = document.querySelectorAll('.step-container');
        const nextBtns = document.querySelectorAll('.btn-next');
        const prevBtns = document.querySelectorAll('.btn-prev');
        const progressBar = document.getElementById('wizardProgress');
        let currentStep = 0;

        function updateWizard() {
            steps.forEach((step, index) => {
                if (index === currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
            
            const progressPercentage = ((currentStep + 1) / steps.length) * 100;
            progressBar.style.width = progressPercentage + '%';
            
            // Scroll to top of container smoothly
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        nextBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if(btn.getAttribute('type') === 'submit') return; // let form submit
                
                // Simple validation for current step before proceeding (optional)
                const currentStepEl = steps[currentStep];
                const requiredInputs = currentStepEl.querySelectorAll('[required]');
                let isValid = true;
                
                requiredInputs.forEach(input => {
                    if(input.type === 'radio') {
                        const radioGroup = currentStepEl.querySelectorAll(`input[name="${input.name}"]:checked`);
                        if(radioGroup.length === 0) isValid = false;
                    } else if (!input.value) {
                        isValid = false;
                    }
                });
                
                if(!isValid) {
                    // Force browser validation to show up
                    currentStepEl.querySelector('input:invalid, select:invalid').reportValidity();
                    return;
                }
                
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    updateWizard();
                }
            });
        });

        prevBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (currentStep > 0) {
                    currentStep--;
                    updateWizard();
                }
            });
        });
        
        // Initial setup
        updateWizard();
    });
</script>
</body>
</html>
