<?php

declare(strict_types=1);

require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');

if (!PdoStagedScreeningStore::schemaIsReady($pdo)) {
    http_response_code(503);
    header('Retry-After: 300');
    exit('Layanan skrining sedang disiapkan. Silakan coba kembali beberapa saat lagi.');
}

$userId = (int) $_SESSION['user_id'];
$error = '';
$store = new PdoStagedScreeningStore($pdo);
$screeningService = new StagedScreeningService($store);

$userStatement = $pdo->prepare('SELECT nama, username, kelas FROM users WHERE id = ? AND role = \'siswa\' LIMIT 1');
$userStatement->execute([$userId]);
$user = $userStatement->fetch();
if (!$user) {
    http_response_code(404);
    exit('Akun siswa tidak ditemukan.');
}

$allowedClasses = ['Kelas VII', 'Kelas VIII', 'Kelas IX', 'Kelas X', 'Kelas XI', 'Kelas XII'];
$savedClass = trim((string) ($user['kelas'] ?? ''));
$selectedClass = '';
foreach ($allowedClasses as $class) {
    if (stripos($savedClass, $class) === 0 || strcasecmp($savedClass, substr($class, 6)) === 0) {
        $selectedClass = $class;
        break;
    }
}

$pendingRisk = $screeningService->pendingRiskFactors($userId);
$step = $pendingRisk !== null ? 'risk' : 'symptoms';

if ($step === 'symptoms') {
    $latestStatement = $pdo->prepare(
        'SELECT created_at FROM kuesioner
         WHERE user_id = ? AND archived_at IS NULL
           AND history_only_at IS NULL
         ORDER BY created_at DESC, id DESC LIMIT 1'
    );
    $latestStatement->execute([$userId]);
    $latestCreatedAt = $latestStatement->fetchColumn();
    $eligibility = (new QuestionnaireEligibility())->forLatestSubmission(
        is_string($latestCreatedAt) ? $latestCreatedAt : null
    );
    if (!$eligibility['allowed']) {
        header('Location: dashboard.php?cooldown=1', true, 303);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail(csrfTokenFromRequest($_POST, $_SERVER));

    try {
        $action = enumValue($_POST['action'] ?? null, ['symptoms', 'risk']);
        if ($action === 'symptoms') {
            if ($step !== 'symptoms') {
                throw new InvalidArgumentException('Selesaikan faktor risiko yang masih terbuka terlebih dahulu.');
            }
            $result = $screeningService->submitSymptoms($userId, $_POST);
            $destination = $result['risk_eligible']
                ? 'kuesioner.php?questionnaire_id=' . $result['questionnaire_id']
                : 'hasil_deteksi.php?questionnaire_id=' . $result['questionnaire_id'];
            header('Location: ' . $destination, true, 303);
            exit;
        }

        if ($step !== 'risk' || $pendingRisk === null) {
            throw new InvalidArgumentException('Tahap faktor risiko tidak tersedia.');
        }
        $questionnaireId = (int) boundedInt($_POST['questionnaire_id'] ?? null, 1, PHP_INT_MAX);
        if ($questionnaireId !== (int) $pendingRisk['id']) {
            throw new InvalidArgumentException('Kuesioner faktor risiko tidak sesuai.');
        }
        $result = $screeningService->submitRiskFactors($userId, $questionnaireId, $_POST);
        header('Location: hasil_deteksi.php?questionnaire_id=' . $result['questionnaire_id'], true, 303);
        exit;
    } catch (Throwable $exception) {
        akrabLog('warn', 'staged_screening_submission_failed', [
            'exception_class' => get_class($exception),
            'outcome' => 'rejected',
        ]);
        $error = $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : publicErrorMessage();
    }
}

$symptomQuestions = [
    'Sahabat merasakan cepat lelah bila beraktivitas',
    'Sahabat merasakan pusing',
    'Sahabat merasakan mata berkunang-kunang',
    'Sahabat merasakan bagian ujung tangan atau kaki sering dingin',
    'Sahabat merasakan suka sempoyongan',
    'Sahabat merasakan berdebar-debar walaupun beraktivitas ringan',
    'Sahabat merasakan mengantuk',
    'Sahabat merasakan malas beraktivitas',
    'Sahabat merasakan nafas terasa pendek waktu beraktivitas',
    'Sahabat merasakan pucat',
];
$dietQuestions = [
    'Apakah sahabat ada sarapan setiap hari ?',
    'Apakah sahabat rutin makan siang ?',
    'Apakah sahabat selalu makan malam?',
    'Apakah sahabat ada makan snek antara makan pagi dan siang?',
    'Apakah sahabat ada makan snek antara makan siang dan malam?',
    'Apakah sahabat ada makan lagi atau snek menjelang tidur ?',
];
$mealTimes = [
    'pagi' => 'Pagi',
    'jam_10' => 'Jam 10',
    'siang' => 'Siang',
    'jam_4' => 'Jam 4',
    'malam' => 'Malam',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skrining Gejala Anemia - AKRAB</title>
    <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260830" rel="stylesheet">
    <script src="/assets/vendor/lucide.min.js"></script>
    <style>
        .screening-shell { max-width: 900px; }
        .screening-step { letter-spacing: .04em; }
        .question-card { border: 1px solid #e2e8f0; border-radius: 1rem; }
        .question-card:focus-within { border-color: var(--primary-color); box-shadow: 0 0 0 .2rem rgba(16, 185, 129, .1); }
        .score-value { min-width: 3rem; text-align: center; }
        .form-range { min-height: 2.5rem; }
        .context-note { background: linear-gradient(135deg, #ecfdf5, #f0fdfa); }
    </style>
</head>
<body class="bg-light">
<?php renderImpersonationBanner($pdo, $_SESSION); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="dashboard.php">
            <i data-lucide="arrow-left" aria-hidden="true"></i> AKRAB Siswa
        </a>
    </div>
</nav>

<main class="container screening-shell py-4 py-md-5">
    <header class="text-center mb-4">
        <span class="badge rounded-pill text-bg-success screening-step mb-2">
            <?= $step === 'risk' ? 'TAHAP 2 DARI 2' : 'TAHAP 1 DARI 2' ?>
        </span>
        <h1 class="h3 fw-bold text-primary mb-2">
            <?= $step === 'risk' ? 'Faktor Risiko' : 'Gejala yang Dirasakan' ?>
        </h1>
        <p class="text-muted mx-auto mb-0" style="max-width: 720px;">
            Skrining sederhana tanpa pemeriksaan Hb ini membantu mengenali indikasi awal berdasarkan gejala.
            Hasilnya bukan diagnosis dan tetap perlu dibahas dengan ahli medis sekolah atau tenaga kesehatan.
        </p>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= escape_output($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'symptoms'): ?>
        <div class="alert context-note border-success-subtle" role="note">
            Isi data diri, lalu beri nilai setiap gejala dari 0 (tidak dirasakan) sampai 10 (sangat kuat/sering).
            Faktor risiko baru dapat diakses bila rerata gejala lebih dari 4,6.
        </div>

        <form method="post" id="symptomForm">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="symptoms">

            <section class="card border-0 shadow-sm mb-4" aria-labelledby="profileHeading">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3" id="profileHeading">Data diri siswa</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="studentName">Nama lengkap</label>
                            <input class="form-control" id="studentName" value="<?= escape_output($user['nama']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="studentNumber">NIS/NISN</label>
                            <input class="form-control" id="studentNumber" value="<?= escape_output($user['username']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="birthDate">Tanggal lahir</label>
                            <input type="date" class="form-control" id="birthDate" name="tanggal_lahir"
                                   value="<?= escape_output($_POST['tanggal_lahir'] ?? '') ?>" required aria-describedby="ageDisplay">
                            <div class="form-text" id="ageDisplay">Usia akan dihitung otomatis.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="studentClass">Kelas</label>
                            <select class="form-select" id="studentClass" name="pendidikan" required>
                                <option value="">Pilih kelas</option>
                                <?php foreach ($allowedClasses as $class): ?>
                                    <?php $classSelected = (string) ($_POST['pendidikan'] ?? $selectedClass) === $class; ?>
                                    <option value="<?= escape_output($class) ?>" <?= $classSelected ? 'selected' : '' ?>>
                                        <?= escape_output($class) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <fieldset>
                                <legend class="col-form-label pt-0">Jenis kelamin</legend>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach (['perempuan' => 'Perempuan', 'laki_laki' => 'Laki-laki'] as $value => $label): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin"
                                                   id="gender_<?= escape_output($value) ?>" value="<?= escape_output($value) ?>"
                                                   <?= (string) ($_POST['jenis_kelamin'] ?? '') === $value ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="gender_<?= escape_output($value) ?>"><?= escape_output($label) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="symptomsHeading">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1" id="symptomsHeading">Pertanyaan gejala</h2>
                        <p class="small text-muted mb-0">Semua pertanyaan wajib dijawab.</p>
                    </div>
                    <span class="badge text-bg-light border">0–10</span>
                </div>

                <?php foreach ($symptomQuestions as $offset => $question): ?>
                    <?php
                    $number = $offset + 1;
                    $currentValue = (int) ($_POST['gejala_' . $number] ?? 0);
                    ?>
                    <div class="question-card bg-white p-3 p-md-4 mb-3">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <label class="fw-semibold" for="symptom_<?= $number ?>">
                                <?= $number ?>. <?= escape_output($question) ?>
                            </label>
                            <output class="badge text-bg-primary score-value fs-6" id="symptomValue_<?= $number ?>" for="symptom_<?= $number ?>">
                                <?= $currentValue ?>
                            </output>
                        </div>
                        <input type="range" class="form-range symptom-range" id="symptom_<?= $number ?>"
                               name="gejala_<?= $number ?>" min="0" max="10" step="1" value="<?= $currentValue ?>" required
                               aria-describedby="symptomScale_<?= $number ?>">
                        <div class="d-flex justify-content-between small text-muted" id="symptomScale_<?= $number ?>">
                            <span>0 — Tidak dirasakan</span><span>10 — Sangat kuat/sering</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg fw-bold">Hitung skor gejala</button>
            </div>
        </form>
    <?php endif; ?>

    <?php if ($step === 'risk' && $pendingRisk !== null): ?>
        <div class="alert context-note border-success-subtle" role="status">
            Rerata gejala Anda <strong><?= escape_output(number_format((float) $pendingRisk['rerata_gejala'], 1, ',', '.')) ?></strong>,
            sehingga pertanyaan faktor risiko dapat dilanjutkan. Jawaban berikut dipakai untuk skrining, bukan diagnosis medis.
        </div>

        <form method="post" id="riskForm">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="risk">
            <input type="hidden" name="questionnaire_id" value="<?= (int) $pendingRisk['id'] ?>">

            <?php if (($pendingRisk['jenis_kelamin'] ?? null) === 'laki_laki'): ?>
                <input type="hidden" name="mens_sudah" value="belum">
                <div class="alert alert-info" role="note">
                    Pertanyaan menstruasi tidak berlaku berdasarkan data jenis kelamin pada tahap pertama.
                </div>
            <?php else: ?>
            <section class="card border-0 shadow-sm mb-4" aria-labelledby="menstruationHeading">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3" id="menstruationHeading">A. Riwayat menstruasi</h2>
                    <fieldset class="mb-3">
                        <legend class="fs-6 fw-semibold">Apakah sahabat sudah mengalami menstruasi?</legend>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mens_sudah" id="mensYes" value="ya" required>
                                <label class="form-check-label" for="mensYes">Sudah</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mens_sudah" id="mensNo" value="belum" required>
                                <label class="form-check-label" for="mensNo">Belum</label>
                            </div>
                        </div>
                    </fieldset>

                    <div class="row g-3 d-none" id="menstrualDetails">
                        <div class="col-md-6">
                            <label class="form-label" for="mensAgeYear">Usia pertama kali menstruasi</label>
                            <div class="input-group">
                                <input type="number" class="form-control menstrual-required" id="mensAgeYear" name="mens_usia_th" min="5" max="25" placeholder="Tahun" disabled>
                                <input type="number" class="form-control" name="mens_usia_bln" min="0" max="11" value="0" aria-label="Bulan" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mensRegular">Apakah siklus menstruasi sahabat teratur tiap bulan?</label>
                            <select class="form-select menstrual-required" id="mensRegular" name="mens_teratur" disabled>
                                <option value="">Pilih jawaban</option>
                                <option value="ya">Ya</option>
                                <option value="tidak">Tidak</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mensDuration">Berapa lama sahabat mengalami menstruasi setiap bulannya?</label>
                            <div class="input-group">
                                <input type="number" class="form-control menstrual-required" id="mensDuration" name="mens_lama" min="1" max="15" disabled>
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mensCycle">Berapa jarak antara siklus setiap bulannya?</label>
                            <div class="input-group">
                                <input type="number" class="form-control menstrual-required" id="mensCycle" name="mens_jarak_siklus" min="1" max="100" disabled>
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section class="card border-0 shadow-sm mb-4" aria-labelledby="dietHeading">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-2" id="dietHeading">B. Pola makan sehari-hari</h2>
                    <p class="text-muted">Isilah tabel berikut ini sesuai makanan yang sahabat makan setiap hari.</p>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light"><tr><th scope="col">Waktu</th><th scope="col">Makanan</th><th scope="col">Jumlah</th></tr></thead>
                            <tbody>
                            <?php foreach ($mealTimes as $key => $label): ?>
                                <tr>
                                    <th scope="row"><?= escape_output($label) ?></th>
                                    <td><input class="form-control" name="makanan_<?= escape_output($key) ?>" maxlength="150" aria-label="Makanan <?= escape_output($label) ?>"></td>
                                    <td><input class="form-control" name="jumlah_<?= escape_output($key) ?>" maxlength="80" aria-label="Jumlah <?= escape_output($label) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php foreach ($dietQuestions as $offset => $question): ?>
                        <?php $number = $offset + 1; ?>
                        <div class="question-card p-3 mb-3">
                            <label class="form-label fw-semibold" for="diet_<?= $number ?>">
                                <?= $number ?>. <?= escape_output($question) ?>
                            </label>
                            <select class="form-select" id="diet_<?= $number ?>" name="makan_<?= $number ?>" required>
                                <option value="">Pilih jawaban</option>
                                <option value="selalu">Selalu</option>
                                <option value="kadang">Kadang-kadang</option>
                                <option value="tidak">Tidak pernah</option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-bold">Lihat hasil skrining</button>
            </div>
        </form>
    <?php endif; ?>
</main>

<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app-init.js?v=20260831-safe-install"></script>
<script>
document.querySelectorAll('.symptom-range').forEach(function (range) {
    const number = range.id.replace('symptom_', '');
    const output = document.getElementById('symptomValue_' + number);
    range.addEventListener('input', function () { output.value = range.value; output.textContent = range.value; });
});

const birthDate = document.getElementById('birthDate');
if (birthDate) {
    const ageDisplay = document.getElementById('ageDisplay');
    const updateAge = function () {
        if (!birthDate.value) { ageDisplay.textContent = 'Usia akan dihitung otomatis.'; return; }
        const birth = new Date(birthDate.value + 'T00:00:00');
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDelta = today.getMonth() - birth.getMonth();
        if (monthDelta < 0 || (monthDelta === 0 && today.getDate() < birth.getDate())) age--;
        ageDisplay.textContent = Number.isFinite(age) ? 'Usia: ' + age + ' tahun' : 'Tanggal lahir tidak valid.';
    };
    birthDate.addEventListener('change', updateAge);
    updateAge();
}

const menstruationChoices = document.querySelectorAll('input[name="mens_sudah"]');
if (menstruationChoices.length) {
    const details = document.getElementById('menstrualDetails');
    const detailInputs = details.querySelectorAll('input, select');
    const updateMenstruation = function () {
        const selected = document.querySelector('input[name="mens_sudah"]:checked');
        const started = selected && selected.value === 'ya';
        details.classList.toggle('d-none', !started);
        detailInputs.forEach(function (input) {
            input.disabled = !started;
            input.required = started && input.classList.contains('menstrual-required');
        });
    };
    menstruationChoices.forEach(function (choice) { choice.addEventListener('change', updateMenstruation); });
    updateMenstruation();
}

document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
        const button = form.querySelector('button[type="submit"]');
        if (button && form.checkValidity()) {
            button.disabled = true;
            button.textContent = 'Menyimpan...';
        }
    });
});

if (window.lucide) window.lucide.createIcons();
</script>
</body>
</html>
