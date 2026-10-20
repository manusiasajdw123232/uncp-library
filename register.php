<?php
require __DIR__.'/app/core/bootstrap.php';
$error=null;
if(is_post()){
 csrf_check(); $nim=trim($_POST['nim']??'');$name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$class=trim($_POST['class_name']??'');$program=trim($_POST['study_program']??'');$faculty=trim($_POST['faculty']??'');$semester=(int)($_POST['semester']??0);$password=$_POST['password']??'';$confirm=$_POST['confirm_password']??'';
 if($password!==$confirm)$error='Konfirmasi password tidak sesuai.'; elseif(strlen($password)<8)$error='Password minimal 8 karakter.'; elseif(!$nim||!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$program||$semester<1||$semester>14)$error='Lengkapi data pendaftaran dengan benar.';
 else{try{$rid=$pdo->query("SELECT id FROM roles WHERE name='pengguna'")->fetchColumn();$st=$pdo->prepare('INSERT INTO users(role_id,nim,name,email,phone,class_name,study_program,faculty,semester,password_hash) VALUES(?,?,?,?,?,?,?,?,?,?)');$st->execute([$rid,$nim,$name,$email,$phone,$class,$program,$faculty,$semester,password_hash($password,PASSWORD_DEFAULT)]);$id=(int)$pdo->lastInsertId();log_activity($pdo,$id,'pengguna','Pendaftaran','Akun pengguna dibuat');notify_user($pdo,$id,'Akun berhasil dibuat','Akun pengguna kamu berhasil dibuat. Silakan login untuk mulai menggunakan perpustakaan.');flash('success','Pendaftaran berhasil. Silakan login.');redirect('/login.php');}catch(Throwable $e){$error=$e->getCode()==='23000'?'NIM atau email sudah terdaftar.':'Pendaftaran gagal.';}}
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
    <title>Daftar Akun — Perpustakaan UNCP</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page public-fixed-theme">
    <div class="auth-card register-card">
        <div class="logo-row">
            <img
                src="/assets/images/logo-uncp.svg"
                alt="Logo UNCP"
                class="brand-logo"
            >

            <div>
                <b>DAFTAR AKUN PENGGUNA</b>
                <small
                    class="muted"
                    style="display: block; margin-top: 4px"
                >
                    Pendaftaran hanya untuk pengguna/mahasiswa perpustakaan
                </small>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_input() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input class="input" name="name" required>
                </div>

                <div class="form-group">
                    <label>NIM</label>
                    <input class="input" name="nim" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input
                        class="input"
                        type="email"
                        name="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input class="input" name="phone">
                </div>

                <div class="form-group">
                    <label>Kelas</label>
                    <input class="input" name="class_name">
                </div>

                <div class="form-group">
                    <label>Program Studi</label>
                    <input class="input" name="study_program" required>
                </div>

                <div class="form-group">
                    <label>Fakultas</label>
                    <input class="input" name="faculty">
                </div>

                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester">
                        <?php for ($i = 1; $i <= 14; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input
                        class="input"
                        type="password"
                        name="password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input
                        class="input"
                        type="password"
                        name="confirm_password"
                        required
                    >
                </div>
            </div>

            <div class="mobile-stack" style="margin-top: 16px">
                <button class="btn btn-primary">Buat Akun</button>
                <a class="btn btn-light" href="/login.php">Kembali Login</a>
            </div>
        </form>
    </div>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
