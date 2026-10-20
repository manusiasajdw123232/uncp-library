<?php

require __DIR__ . '/app/core/bootstrap.php';

if ($current = auth_user()) {
    redirect('/' . role_home($current));
}

$error = null;

if (is_post()) {
    csrf_check();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '') {
        $error = 'Tuliskan username, NIM, atau email.';
    } elseif ($password === '') {
        $error = 'Masukkan password.';
    } else {
        try {
            $st = $pdo->prepare(
                "SELECT u.*,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE (u.nim=? OR u.username=? OR u.email=?) AND u.status='aktif' LIMIT 1"
            );
            $st->execute([$identifier, $identifier, $identifier]);
            $u = $st->fetch();

            if ($u && password_verify($password, $u['password_hash'])) {
                session_regenerate_id(true);
                unset($u['password_hash']);

                $now = date('Y-m-d H:i:s');
                $u['last_login_at'] = $now;
                $_SESSION['user'] = $u;

                $pdo
                    ->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')
                    ->execute([safe_int($u['id'])]);

                log_activity(
                    $pdo,
                    safe_int($u['id']),
                    $u['role_name'],
                    'Login',
                    'Masuk ke sistem'
                );

                flash(
                    'success',
                    'Login berhasil. Selamat datang, ' .
                        ($u['name'] ?? 'Pengguna') .
                        '.'
                );

                redirect('/' . role_home($u));
            }

            $error = 'Username, NIM, email, atau password tidak sesuai.';
        } catch (Throwable $e) {
            $error = 'Login tidak dapat diproses. Silakan coba lagi.';
        }
    }
}
?>
<!doctype html>
<html
    lang="id"
    data-theme="dark"
    data-theme-mode="fixed"
    data-text-size="medium"
    data-density="normal"
    data-motion="light"
    data-sidebar-mode="default"
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
    <title>Login — Perpustakaan UNCP</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page public-fixed-theme">
    <main class="auth-card" aria-labelledby="loginTitle">
        <div class="auth-kicker">PORTAL DIGITAL PERPUSTAKAAN</div>

        <div class="auth-brand">
            <img
                src="/assets/images/logo-uncp.svg"
                alt="Logo UNCP"
                class="brand-logo"
            >

            <h1 id="loginTitle">
                PERPUSTAKAAN UNIVERSITAS COKROAMINOTO PALOPO
            </h1>

            <p>
                Masuk untuk mengakses katalog buku dan repository akademik UNCP.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert error" role="alert" aria-live="polite">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="loginForm" novalidate>
            <?= csrf_input() ?>

            <div class="form-group">
                <label for="identifier">Username / NIM / Email</label>

                <input
                    class="input"
                    id="identifier"
                    name="identifier"
                    autocomplete="username"
                    placeholder="Masukkan username, NIM, atau email"
                    required
                    value="<?= e($_POST['identifier'] ?? '') ?>"
                >
            </div>

            <div class="form-group" style="margin-top: 14px">
                <label for="password">Password</label>

                <div class="password-wrap">
                    <input
                        class="input"
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        required
                    >

                    <button
                        type="button"
                        class="btn btn-light password-toggle"
                        id="passwordToggle"
                        aria-label="Tampilkan password"
                    >
                        Lihat
                    </button>
                </div>
            </div>

            <div class="auth-actions">
                <button
                    class="btn btn-primary min-touch"
                    id="loginBtn"
                    type="submit"
                >
                    Masuk
                </button>
            </div>
        </form>

        <div class="auth-meta">
            Belum punya akun?
            <a href="/register.php">Daftar Akun Pengguna</a>
        </div>

        <div class="auth-support">
            Sistem akan menentukan akses
            <b>Pengguna</b>, <b>Admin</b>, atau <b>Penjaga</b>
            secara otomatis berdasarkan role akun di database.
        </div>
    </main>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/app.js"></script>

    <script>
        (function () {
            const form = document.getElementById('loginForm');
            const identifier = document.getElementById('identifier');
            const password = document.getElementById('password');
            const toggle = document.getElementById('passwordToggle');
            const button = document.getElementById('loginBtn');

            toggle?.addEventListener('click', () => {
                const visible = password.type === 'password';

                password.type = visible ? 'text' : 'password';
                toggle.textContent = visible ? 'Sembunyikan' : 'Lihat';
                toggle.setAttribute(
                    'aria-label',
                    visible
                        ? 'Sembunyikan password'
                        : 'Tampilkan password'
                );
            });

            form?.addEventListener('submit', (event) => {
                if (!identifier.value.trim()) {
                    event.preventDefault();
                    identifier.setCustomValidity(
                        'Tuliskan username, NIM, atau email.'
                    );
                    identifier.reportValidity();
                    identifier.setCustomValidity('');
                    return;
                }

                if (!password.value) {
                    event.preventDefault();
                    password.setCustomValidity('Masukkan password.');
                    password.reportValidity();
                    password.setCustomValidity('');
                    return;
                }

                button.disabled = true;
                button.textContent = 'Memproses…';
            });
        })();
    </script>
</body>
</html>
