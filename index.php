<?php
require __DIR__ . '/app/core/bootstrap.php';
?>
<!doctype html>
<html
    lang="id"
    data-theme="dark"
    data-theme-mode="fixed"
    data-public-theme="fixed-dark"
>
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#b5121b">
    <title>Perpustakaan UNCP</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-fixed-theme">
    <nav class="public-nav">
        <div class="public-nav-inner">
            <a href="/" class="public-brand">
                <img
                    src="/assets/images/logo-uncp.svg"
                    alt="Logo UNCP"
                    class="brand-logo"
                >
                <div>
                    <b>PERPUSTAKAAN UNCP</b>
                    <small>Universitas Cokroaminoto Palopo</small>
                </div>
            </a>

            <div class="public-actions">
                <a class="btn btn-light" href="/login.php">Masuk</a>
                <a class="btn btn-primary" href="/register.php">Daftar</a>
            </div>
        </div>
    </nav>

    <main class="public-main">
        <section class="public-wrap">
            <div class="hero hero-public">
                <div class="hero-content">
                    <span class="public-kicker">
                        UNIVERSITAS COKROAMINOTO PALOPO
                    </span>

                    <h1>
                        Perpustakaan digital untuk menemukan dan berbagi
                        pengetahuan.
                    </h1>

                    <p>
                        Jelajahi katalog buku, repository skripsi, baca dokumen
                        digital yang tersedia, dan kirim karya untuk diverifikasi
                        pengelola perpustakaan.
                    </p>

                    <div class="public-cta">
                        <a class="btn btn-primary" href="/login.php">
                            Mulai Sekarang
                        </a>
                        <a class="btn btn-light" href="/register.php">
                            Daftar Akun
                        </a>
                    </div>
                </div>

                <div class="hero-glow" aria-hidden="true"></div>
            </div>

            <section class="landing-features">
                <article class="card feature-card">
                    <span class="feature-icon">▣</span>
                    <h2>Katalog Buku</h2>
                    <p>
                        Temukan koleksi berdasarkan judul, penulis, kategori,
                        ISBN, dan tahun terbit.
                    </p>
                </article>

                <article class="card feature-card">
                    <span class="feature-icon">◫</span>
                    <h2>Repository Skripsi</h2>
                    <p>
                        Akses karya ilmiah dan baca PDF ketika file digital
                        tersedia.
                    </p>
                </article>

                <article class="card feature-card">
                    <span class="feature-icon">✓</span>
                    <h2>Pengajuan &amp; Verifikasi</h2>
                    <p>
                        Kirim buku atau skripsi dan ikuti status verifikasi
                        secara terstruktur.
                    </p>
                </article>
            </section>

            <section class="landing-stats">
                <div class="card stat">
                    <div class="label">Buku Disetujui</div>
                    <div class="num" id="totalBooks">—</div>
                </div>

                <div class="card stat">
                    <div class="label">Pengguna Aktif</div>
                    <div class="num" id="activeUsers">—</div>
                </div>

                <div class="card stat">
                    <div class="label">Repository Publik</div>
                    <div class="num" id="publicRepo">—</div>
                </div>

                <div class="card stat">
                    <div class="label">Menunggu Verifikasi</div>
                    <div class="num" id="pendingSubs">—</div>
                </div>
            </section>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <span>
                © <?= date('Y') ?> Perpustakaan Universitas Cokroaminoto Palopo
            </span>
            <span>Portal Katalog &amp; Repository Akademik UNCP</span>
        </div>
    </footer>

    <script src="/assets/js/theme.js"></script>
    <script>
        (function () {
            const ids = {
                totalBooks: 'total_books',
                activeUsers: 'active_users',
                publicRepo: 'public_repository',
                pendingSubs: 'pending_submissions',
            };

            let loaded = false;

            async function refresh() {
                try {
                    const response = await fetch(
                        '/api/dashboard/statistics/index.php',
                        {
                            headers: {
                                Accept: 'application/json',
                            },
                            cache: 'no-store',
                        }
                    );

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const data = await response.json();

                    Object.entries(ids).forEach(([id, key]) => {
                        document.getElementById(id).textContent =
                            Number(data[key]) || 0;
                    });

                    loaded = true;
                } catch (error) {
                    if (!loaded) {
                        Object.keys(ids).forEach((id) => {
                            document.getElementById(id).textContent = '—';
                        });
                    }
                }
            }

            refresh();
            setInterval(refresh, 15000);
        })();
    </script>
</body>
</html>
