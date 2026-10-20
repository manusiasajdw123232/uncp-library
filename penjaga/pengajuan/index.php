<?php

require __DIR__ . '/../../app/core/bootstrap.php';

$u = require_roles(['penjaga']);

require __DIR__ . '/../../app/helpers/layout.php';

render_start(
    'Pengajuan Masuk',
    'Ringkasan semua pengajuan baru',
    $u
);

dashboard_nav('penjaga');

render_mid(
    'Pengajuan Masuk',
    'Buku dan skripsi yang masuk untuk diperiksa',
    $u
);

$books = (int) $pdo
    ->query("SELECT COUNT(*) FROM books WHERE approval_status<>'disetujui'")
    ->fetchColumn();

$thesis = (int) $pdo
    ->query(
        "SELECT COUNT(*) FROM repositories WHERE type='skripsi' AND status<>'publik'"
    )
    ->fetchColumn();
?>

<div class="grid grid-2">
    <a class="card" href="/penjaga/verifikasi/buku.php">
        <div class="label muted">Pengajuan Buku</div>

        <div
            class="num"
            style="font-size: 36px; font-weight: 800; margin: 7px 0"
        >
            <?= $books ?>
        </div>

        <span class="btn btn-primary">
            Proses Verifikasi
        </span>
    </a>

    <a class="card" href="/penjaga/verifikasi/skripsi.php">
        <div class="label muted">Pengajuan Skripsi</div>

        <div
            class="num"
            style="font-size: 36px; font-weight: 800; margin: 7px 0"
        >
            <?= $thesis ?>
        </div>

        <span class="btn btn-primary">
            Proses Verifikasi
        </span>
    </a>
</div>

<?php render_end(); ?>
