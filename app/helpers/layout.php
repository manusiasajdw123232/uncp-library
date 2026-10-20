<?php

function nav_link(
    string $href,
    string $label,
    string $icon = '•'
): string {
    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    $active = str_contains($current, $href) ? ' active' : '';

    return '<a class="' .
        $active .
        '" href="' .
        $href .
        '" title="' .
        e($label) .
        '" data-nav-link><span class="nav-icon" aria-hidden="true">' .
        $icon .
        '</span><span class="nav-label">' .
        e($label) .
        '</span></a>';
}

function render_start(
    string $title,
    string $subtitle = '',
    array $user = []
): void {
?>
<!doctype html>
<html
    lang="id"
    data-theme="light"
    data-theme-mode="light"
    data-text-size="medium"
    data-density="normal"
    data-motion="light"
    data-sidebar-mode="default"
>
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#b5121b">
    <title><?= e($title) ?> — Perpustakaan UNCP</title>

    <script>
        (function () {
            try {
                var root = document.documentElement;
                var mode = localStorage.getItem('uncp-theme') || 'light';
                var resolved =
                    mode === 'system' && window.matchMedia
                        ? window.matchMedia('(prefers-color-scheme: dark)').matches
                            ? 'dark'
                            : 'light'
                        : mode;

                root.dataset.theme = resolved;
                root.dataset.themeMode = mode;
                root.dataset.textSize =
                    localStorage.getItem('uncp-text-size') || 'medium';
                root.dataset.density =
                    localStorage.getItem('uncp-density') || 'normal';
                root.dataset.motion =
                    localStorage.getItem('uncp-motion') || 'light';
                root.dataset.sidebarMode =
                    localStorage.getItem('uncp-sidebar') || 'default';
            } catch (e) {}
        })();
    </script>

    <style>
        html,
        html body {
            background: #f4f6f8;
        }

        html[data-theme="dark"],
        html[data-theme="dark"] body {
            background: #0d0f12;
            color: #eef0f4;
        }
    </style>

    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-body">
<div class="app-shell">
    <aside
        class="sidebar"
        id="sidebar"
        aria-label="Navigasi utama"
    >
        <div class="brand">
            <img
                src="/assets/images/logo-uncp.svg"
                alt="Logo UNCP"
                class="brand-logo"
            >

            <div class="brand-copy">
                <b>Perpustakaan UNCP</b>
                <small>Universitas Cokroaminoto Palopo</small>
            </div>
        </div>

        <nav
            class="nav"
            id="mainNav"
            aria-label="Menu utama"
        >
<?php
}

function nav_section(string $title, array $links): void
{
    echo '<div class="nav-group"><div class="nav-title">' .
        e($title) .
        '</div>';

    foreach ($links as $link) {
        echo nav_link(
            $link[0],
            $link[1],
            $link[2] ?? '•'
        );
    }

    echo '</div>';
}

function render_mid(
    string $title,
    string $subtitle,
    array $user = []
): void {
?>
        </nav>

        <div class="sidebar-footer">
            <a
                href="/logout.php"
                class="logout-link"
                data-nav-link
                data-confirm="Apakah Anda yakin ingin keluar?"
            >
                <span aria-hidden="true">↪</span>
                <span class="nav-label">Keluar</span>
            </a>
        </div>
    </aside>

    <div
        class="drawer-overlay"
        id="drawerOverlay"
        onclick="closeSidebar()"
        aria-hidden="true"
    ></div>

    <main class="main">
        <header class="topbar">
            <button
                class="mobile-menu"
                type="button"
                onclick="toggleSidebar()"
                aria-controls="sidebar"
                aria-expanded="false"
                aria-label="Buka menu"
            >
                ☰
            </button>

            <div class="topbar-title">
                <b><?= e($title) ?></b>
                <div class="muted topbar-subtitle">
                    <?= e($subtitle) ?>
                </div>
            </div>

            <div class="top-actions">
                <a
                    class="avatar avatar-link"
                    href="<?= (
                        !empty($user['is_admin']) ||
                        ($user['role_name'] ?? '') === 'admin'
                    )
                        ? '/admin/profile/index.php'
                        : (($user['role_name'] ?? '') === 'penjaga'
                            ? '/penjaga/profile/index.php'
                            : '/pengguna/profile/index.php') ?>"
                    aria-label="Buka profil"
                >
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img
                            src="<?= e($user['profile_photo']) ?>"
                            alt="Foto profil"
                        >
                    <?php else: ?>
                        <?= e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <section class="content">
<?php
    $flash = get_flash();

    if ($flash):
?>
            <div
                class="flash-toast <?= e(
                    $flash['type'] === 'success' ? 'success' : 'error'
                ) ?>"
                role="status"
                aria-live="polite"
                data-toast
            >
                <span class="toast-icon" aria-hidden="true">
                    <?= e($flash['type'] === 'success' ? '✓' : '!') ?>
                </span>

                <div class="toast-copy">
                    <b>
                        <?= e(
                            $flash['type'] === 'success'
                                ? 'Berhasil'
                                : 'Perhatian'
                        ) ?>
                    </b>
                    <span><?= e($flash['message']) ?></span>
                </div>

                <button
                    type="button"
                    class="toast-close"
                    aria-label="Tutup notifikasi"
                    data-toast-close
                >
                    ×
                </button>
            </div>
<?php
    endif;
}

function render_end(): void
{
?>
        </section>
    </main>
</div>

<script src="/assets/js/theme.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
<?php
}

function dashboard_nav(string $role): void
{
    if ($role === 'admin') {
        nav_section(
            'Utama',
            [
                ['/admin/dashboard/index.php', 'Dashboard', '⌂'],
                ['/admin/users/index.php', 'Data Pengguna', '♙'],
                ['/admin/books/index.php', 'Data Buku', '▣'],
                ['/admin/repository/index.php', 'Data Skripsi', '◫'],
            ]
        );

        nav_section(
            'Verifikasi',
            [
                ['/admin/verifikasi/buku.php', 'Verifikasi Buku', '✓'],
                ['/admin/verifikasi/skripsi.php', 'Verifikasi Skripsi', '✓'],
                ['/admin/verifikasi/riwayat.php', 'Riwayat Verifikasi', '◷'],
            ]
        );

        nav_section(
            'Sistem',
            [
                ['/admin/settings/index.php', 'Pengaturan', '⚙'],
                ['/admin/staff/index.php', 'Akun Khusus', '♜'],
                ['/admin/profile/index.php', 'Profil', '♙'],
            ]
        );
    } elseif ($role === 'penjaga') {
        nav_section(
            'Utama',
            [
                ['/penjaga/dashboard/index.php', 'Dashboard', '⌂'],
                ['/penjaga/books/index.php', 'Katalog Buku', '▣'],
                ['/penjaga/repository/index.php', 'Katalog Skripsi', '◫'],
            ]
        );

        nav_section(
            'Verifikasi',
            [
                ['/penjaga/verifikasi/buku.php', 'Verifikasi Buku', '✓'],
                ['/penjaga/verifikasi/skripsi.php', 'Verifikasi Skripsi', '✓'],
                ['/penjaga/pengajuan/index.php', 'Pengajuan Masuk', '⇩'],
                ['/penjaga/verifikasi/riwayat.php', 'Riwayat Verifikasi', '◷'],
            ]
        );

        nav_section(
            'Akun',
            [
                ['/penjaga/profile/index.php', 'Profil', '♙'],
                ['/penjaga/settings/index.php', 'Pengaturan', '⚙'],
            ]
        );
    } else {
        nav_section(
            'Perpustakaan',
            [
                ['/pengguna/dashboard/index.php', 'Dashboard', '⌂'],
                ['/pengguna/katalog/index.php', 'Katalog Buku', '▣'],
                ['/pengguna/skripsi/index.php', 'Katalog Skripsi', '◫'],
                ['/pengguna/milik/index.php', 'Buku/Skripsi Saya', '▤'],
                ['/pengguna/tambah/index.php', 'Tambah Buku/Skripsi', '＋'],
                ['/pengguna/riwayat/index.php', 'Riwayat Pengajuan', '◷'],
            ]
        );

        nav_section(
            'Akun',
            [
                ['/pengguna/profile/index.php', 'Profil', '♙'],
                ['/pengguna/settings/index.php', 'Pengaturan', '⚙'],
            ]
        );
    }
}
