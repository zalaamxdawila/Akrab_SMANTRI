<?php

declare(strict_types=1);

function renderSuperadminHeader(string $title, string $active): void
{
    $navigation = [
        'dashboard' => ['Dashboard', 'dashboard.php'],
        'users' => ['Pengguna', 'users.php'],
        'audit' => ['Audit', 'audit.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?= escape_output($title) ?> — Superadmin AKRAB</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%23087f5b'/%3E%3Ctext x='32' y='43' text-anchor='middle' font-size='36' fill='white' font-family='Arial'%3EA%3C/text%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=20260729" rel="stylesheet">
    <link href="../assets/css/superadmin.css?v=20260729" rel="stylesheet">
</head>
<body class="superadmin-body">
<a class="skip-link" href="#main-content">Lewati ke konten utama</a>
<nav class="navbar navbar-expand-lg sticky-top superadmin-nav" aria-label="Navigasi utama superadmin">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand" href="dashboard.php" aria-label="AKRAB Superadmin, beranda">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>AKRAB <small>MASTER</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#superadminNavigation"
                aria-controls="superadminNavigation" aria-expanded="false"
                aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="superadminNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php foreach ($navigation as $key => [$label, $href]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $active === $key ? 'active' : '' ?>"
                           href="<?= escape_output($href) ?>"
                           <?= $active === $key ? 'aria-current="page"' : '' ?>>
                            <?= escape_output($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sm btn-outline-danger" href="../logout.php">Keluar</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main id="main-content" class="container-fluid superadmin-main px-3 px-lg-4 py-4">
    <header class="page-heading mb-4">
        <p class="eyebrow mb-1">Pusat kendali sistem</p>
        <h1 class="h2 mb-1"><?= escape_output($title) ?></h1>
        <p class="text-muted mb-0">Mode baca-saja · perubahan data belum diaktifkan</p>
    </header>
<?php
}

function renderSuperadminFooter(): void
{
    ?>
</main>
<footer class="superadmin-footer px-3 px-lg-4 py-3">
    <span>AKRAB Superadmin</span>
    <span>Aktivitas sensitif dilindungi audit</span>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
