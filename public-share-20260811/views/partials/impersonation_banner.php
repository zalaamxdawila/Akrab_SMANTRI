<?php

declare(strict_types=1);

function renderImpersonationBanner(PDO $pdo, array $session): void
{
    $id = (int) ($session['_impersonation_session_id'] ?? 0);
    if ($id < 1) {
        return;
    }
    $statement = $pdo->prepare(
        "SELECT i.expires_at, u.nama, u.role
         FROM impersonation_sessions i
         JOIN users u ON u.id = i.target_user_id
         WHERE i.id = ? AND i.status = 'active'"
    );
    $statement->execute([$id]);
    $record = $statement->fetch();
    if (!$record) {
        return;
    }
    $expiry = strtotime((string) $record['expires_at']) ?: time();
    $remaining = max(0, $expiry - time());
    ?>
<link rel="stylesheet" href="<?= escape_output(BASE_URL . 'assets/css/impersonation.css?v=20260729') ?>">
<aside class="impersonation-banner" role="status" aria-label="Mode Login As aktif">
    <div>
        <strong>Login As aktif:</strong>
        <?= escape_output($record['nama']) ?>
        (<?= escape_output($record['role']) ?>)
        · tersisa <span data-impersonation-countdown="<?= $remaining ?>">
            <?= intdiv($remaining, 60) ?> menit
        </span>
    </div>
    <form method="post" action="<?= escape_output(BASE_URL . 'end_impersonation.php') ?>">
        <?= csrfInput() ?>
        <button class="btn btn-sm btn-light" type="submit">Kembali ke Superadmin</button>
    </form>
</aside>
<script src="<?= escape_output(BASE_URL . 'assets/js/impersonation.js?v=20260729') ?>" defer></script>
<?php
}
