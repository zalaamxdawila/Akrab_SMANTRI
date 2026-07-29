<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/app/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/app/Repositories/SuperadminOperationalRepository.php';
require_once dirname(__DIR__) . '/views/superadmin/layout.php';

function renderOperationsList(
    PDO $pdo,
    array $session,
    string $section,
    array $types,
    string $actionRoute
): void {
    try {
        SuperadminGuard::authorize($pdo, $session);
        $type = normalizeText($_GET['type'] ?? array_key_first($types), 20);
        if (!isset($types[$type])) {
            throw new InvalidArgumentException('Jenis tidak valid.');
        }
        $search = normalizeText($_GET['search'] ?? '', 100);
        $archived = ($_GET['archived'] ?? '') === '1';
        $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
        $result = (new SuperadminOperationalRepository($pdo))
            ->paginate($type, $search, $archived, $page, 25);
    } catch (InvalidArgumentException) {
        http_response_code(400);
        exit('Filter tidak valid.');
    } catch (Throwable) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
    renderSuperadminHeader($section, 'operations');
    ?>
    <section class="master-card p-3 p-lg-4 mb-4">
    <form method="get" class="row g-3 align-items-end">
    <div class="col-md-3"><label for="type" class="form-label">Jenis</label>
    <select id="type" name="type" class="form-select">
    <?php foreach ($types as $key => $label): ?><option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>><?= escape_output($label) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-5"><label for="search" class="form-label">Cari</label>
    <input id="search" name="search" class="form-control" maxlength="100" value="<?= escape_output($search) ?>"></div>
    <div class="col-md-2 form-check"><input id="archived" name="archived" value="1" type="checkbox" class="form-check-input" <?= $archived ? 'checked' : '' ?>><label for="archived" class="form-check-label">Arsip saja</label></div>
    <div class="col-md-2"><button class="btn btn-primary" type="submit">Terapkan</button></div>
    </form></section>
    <section class="master-card p-3 p-lg-4"><div class="table-responsive">
    <table class="table master-table"><thead><tr><th>Judul/siswa</th><th>Ringkasan</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($result['items'] as $record): ?><tr>
    <td><?= escape_output($record['title']) ?></td>
    <td><?= escape_output(mb_strimwidth((string) $record['summary'], 0, 120, '…')) ?></td>
    <td><?= escape_output((string) $record['record_date']) ?></td>
    <td><?= $record['archived_at'] ? 'Arsip' : ($record['corrected_at'] ? 'Dikoreksi' : 'Aktif') ?></td>
    <td><?php if (!$record['archived_at']): ?><form method="post" action="<?= escape_output($actionRoute) ?>" class="d-flex flex-column gap-1">
    <?= csrfInput() ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>"><input type="hidden" name="record_type" value="<?= escape_output($type) ?>"><input type="hidden" name="reason" value="data_governance">
    <?php if (in_array($type, ['consultation', 'reply'], true)): ?>
    <textarea name="content" class="form-control form-control-sm" maxlength="10000" aria-label="Isi koreksi"><?= escape_output($record['summary']) ?></textarea>
    <?php if ($type === 'consultation'): ?><select name="status" class="form-select form-select-sm" aria-label="Status konsultasi"><option value="menunggu">Menunggu</option><option value="dijawab">Dijawab</option></select><?php endif; ?>
    <?php elseif ($type === 'article'): ?>
    <input name="title" class="form-control form-control-sm" maxlength="255" value="<?= escape_output($record['title']) ?>" aria-label="Judul artikel">
    <textarea name="content" class="form-control form-control-sm" maxlength="50000" aria-label="Konten artikel"><?= escape_output($record['summary']) ?></textarea>
    <?php elseif ($type === 'advice'): ?>
    <input name="judul_saran" class="form-control form-control-sm" maxlength="100" value="<?= escape_output($record['title']) ?>" aria-label="Judul saran">
    <textarea name="isi_saran" class="form-control form-control-sm" maxlength="10000" aria-label="Isi saran"><?= escape_output($record['summary']) ?></textarea>
    <textarea name="rekomendasi_makanan" class="form-control form-control-sm" maxlength="10000" aria-label="Rekomendasi makanan"></textarea>
    <textarea name="kapan_rujuk_ke_ahli" class="form-control form-control-sm" maxlength="10000" aria-label="Kapan rujuk"></textarea>
    <?php elseif ($type === 'schedule'): ?>
    <input name="jam_pengingat" type="time" class="form-control form-control-sm" aria-label="Jam pengingat">
    <select name="hari" class="form-select form-select-sm" aria-label="Frekuensi"><option value="harian">Harian</option><option value="mingguan">Mingguan</option><option value="saat_menstruasi">Saat menstruasi</option></select>
    <select name="aktif" class="form-select form-select-sm" aria-label="Status jadwal"><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
    <?php elseif ($type === 'delivery'): ?>
    <select name="sudah_dikonfirmasi" class="form-select form-select-sm" aria-label="Konfirmasi penerimaan"><option value="1">Dikonfirmasi</option><option value="0">Belum dikonfirmasi</option></select>
    <?php endif; ?>
    <button class="btn btn-sm btn-outline-primary" name="action" value="correct">Koreksi</button>
    <button class="btn btn-sm btn-warning" name="action" value="archive">Arsipkan</button>
    </form><?php else: ?><span class="text-muted">Historis</span><?php endif; ?></td>
    </tr><?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="5" class="empty-state">Tidak ada data.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php $query = ['type' => $type, 'search' => $search] + ($archived ? ['archived' => '1'] : []); ?>
    <nav aria-label="Halaman data operasional"><ul class="pagination justify-content-center mb-0">
    <li class="page-item <?= $result['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= escape_output(http_build_query($query + ['page' => max(1, $result['page'] - 1)])) ?>">Sebelumnya</a></li>
    <li class="page-item disabled"><span class="page-link">Halaman <?= $result['page'] ?> dari <?= $result['pages'] ?> · <?= $result['total'] ?> data</span></li>
    <li class="page-item <?= $result['page'] >= $result['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="?<?= escape_output(http_build_query($query + ['page' => min($result['pages'], $result['page'] + 1)])) ?>">Berikutnya</a></li>
    </ul></nav>
    </section>
    <?php renderSuperadminFooter();
}
