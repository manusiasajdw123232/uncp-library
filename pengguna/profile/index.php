<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_roles(['pengguna']);
require __DIR__.'/../../app/helpers/profile.php';
require_once __DIR__.'/../../app/helpers/library.php';

$error=null;
if(is_post()){
  csrf_check();
  try{
    $action=$_POST['action']??'';
    if($action==='profile'){
      $name=trim($_POST['name']??'');
      $phone=trim($_POST['phone']??'');
      $class=trim($_POST['class_name']??'');
      $program=trim($_POST['study_program']??'');
      $faculty=trim($_POST['faculty']??'');
      if(!$name||!$program) throw new RuntimeException('Nama dan program studi wajib diisi.');
      $photo=save_profile_photo($_FILES['profile_photo']??[],safe_int($u['id']));
      if($photo){
        $old=profile_photo_path($u['profile_photo']??null);
        $pdo->prepare('UPDATE users SET name=?,phone=?,class_name=?,study_program=?,faculty=?,profile_photo=? WHERE id=?')->execute([$name,$phone,$class,$program,$faculty,$photo,safe_int($u['id'])]);
        if($old) @unlink($old);
      }else{
        $pdo->prepare('UPDATE users SET name=?,phone=?,class_name=?,study_program=?,faculty=? WHERE id=?')->execute([$name,$phone,$class,$program,$faculty,safe_int($u['id'])]);
      }
      $_SESSION['user']=array_merge($_SESSION['user'],['name'=>$name,'phone'=>$phone,'class_name'=>$class,'study_program'=>$program,'faculty'=>$faculty]);
      if($photo) $_SESSION['user']['profile_photo']=$photo;
      flash('success','Profil berhasil diperbarui.');redirect('/pengguna/profile/index.php');
    }
    if($action==='password'){
      $old=$_POST['old_password']??'';$new=$_POST['new_password']??'';$confirm=$_POST['confirm_password']??'';
      $q=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$q->execute([safe_int($u['id'])]);$hash=$q->fetchColumn();
      if(!$hash||!password_verify($old,$hash)) throw new RuntimeException('Password lama tidak sesuai.');
      if(strlen($new)<8) throw new RuntimeException('Password baru minimal 8 karakter.');
      if($new!==$confirm) throw new RuntimeException('Konfirmasi password tidak sesuai.');
      $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),safe_int($u['id'])]);
      flash('success','Password berhasil diubah.');redirect('/pengguna/profile/index.php#ubah-password');
    }
  }catch(Throwable $e){$error=$e->getMessage();}
}
$st=$pdo->prepare('SELECT COUNT(*) FROM books WHERE submitted_by=?');$st->execute([safe_int($u['id'])]);$bookCount=safe_int($st->fetchColumn());
$st=$pdo->prepare('SELECT COUNT(*) FROM repositories WHERE user_id=? AND type="skripsi"');$st->execute([safe_int($u['id'])]);$thesisCount=safe_int($st->fetchColumn());
$st=$pdo->prepare("SELECT COUNT(*) FROM books WHERE submitted_by=? AND approval_status='disetujui'");$st->execute([safe_int($u['id'])]);$approvedBooks=safe_int($st->fetchColumn());
$st=$pdo->prepare("SELECT COUNT(*) FROM books WHERE submitted_by=? AND approval_status='menunggu'");$st->execute([safe_int($u['id'])]);$pendingBooks=safe_int($st->fetchColumn());
$st=$pdo->prepare("SELECT COUNT(*) FROM repositories WHERE user_id=? AND status='publik' AND type='skripsi'");$st->execute([safe_int($u['id'])]);$approvedThesis=safe_int($st->fetchColumn());
$st=$pdo->prepare("SELECT COUNT(*) FROM repositories WHERE user_id=? AND status='menunggu' AND type='skripsi'");$st->execute([safe_int($u['id'])]);$pendingThesis=safe_int($st->fetchColumn());
$st=$pdo->prepare("SELECT COUNT(*) FROM books WHERE submitted_by=? AND approval_status='ditolak'");
$st->execute([safe_int($u['id'])]);$rejectedBooks=safe_int($st->fetchColumn());
$st=$pdo->prepare("SELECT COUNT(*) FROM repositories WHERE user_id=? AND type='skripsi' AND status='ditolak'");
$st->execute([safe_int($u['id'])]);$rejectedThesis=safe_int($st->fetchColumn());
$totalSubmissions=$bookCount+$thesisCount;
$pendingSubmissions=$pendingBooks+$pendingThesis;
$approvedSubmissions=$approvedBooks+$approvedThesis;
$rejectedSubmissions=$rejectedBooks+$rejectedThesis;

$lastActivity=$pdo->prepare("SELECT action,description,created_at FROM activity_logs WHERE user_id=? ORDER BY id DESC LIMIT 5");$lastActivity->execute([safe_int($u['id'])]);$activities=$lastActivity->fetchAll();

require __DIR__.'/../../app/helpers/layout.php';
render_start('Profil','Akun pengguna perpustakaan',$u);dashboard_nav('pengguna');render_mid('Profil','Informasi, aktivitas, dan keamanan akun',$u);
if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif;?>
<div class="grid" style="gap:18px">
  <section class="card profile-hero">
    <div class="profile-photo-wrap">
      <?php if($p=profile_photo_path($u['profile_photo']??null)): ?><img class="profile-photo" loading="lazy" decoding="async" src="<?=e($u['profile_photo'])?>" alt="Foto profil"><?php else: ?><div class="profile-photo-placeholder"><?=e(profile_initial($u))?></div><?php endif;?>
      <label class="profile-edit-badge" for="profilePhoto" title="Ganti foto">✎</label>
    </div>
    <h1 class="profile-name"><?=e($u['name'])?></h1>
    <div class="profile-role">Pengguna · Akun aktif</div>
    <div class="mobile-stack" style="justify-content:center;margin-top:16px">
      <a class="btn btn-primary" href="#edit-profile">Edit Profil</a>
      <a class="btn btn-light" href="#ubah-password">Ubah Password</a>
    </div>
    <form id="quickPhotoForm" method="post" enctype="multipart/form-data" style="display:none">
      <?=csrf_input()?><input type="hidden" name="action" value="profile">
      <input type="hidden" name="name" value="<?=e($u['name'])?>"><input type="hidden" name="class_name" value="<?=e($u['class_name']??'')?>"><input type="hidden" name="study_program" value="<?=e($u['study_program']??'')?>"><input type="hidden" name="faculty" value="<?=e($u['faculty']??'')?>"><input type="hidden" name="phone" value="<?=e($u['phone']??'')?>">
      <input type="file" id="profilePhoto" name="profile_photo" accept="image/jpeg,image/png,image/webp">
    </form>
    <script>document.getElementById('profilePhoto')?.addEventListener('change',()=>document.getElementById('quickPhotoForm').submit());</script>
  </section>

  <section class="card">
    <div class="profile-section-title"><h2 style="margin:0">Ringkasan Aktivitas</h2><span class="badge green">Pengguna</span></div>
    <div class="profile-stats">
      <div class="profile-stat"><small>Jumlah Pengajuan</small><b><?=$totalSubmissions?></b></div>
      <div class="profile-stat"><small>Menunggu</small><b><?=$pendingSubmissions?></b></div>
      <div class="profile-stat"><small>Disetujui</small><b><?=$approvedSubmissions?></b></div>
      <div class="profile-stat"><small>Ditolak</small><b><?=$rejectedSubmissions?></b></div>
    </div>
  </section>

  <div class="grid grid-2">
    <section class="card" id="edit-profile">
      <div class="profile-section-title"><h2 style="margin:0">Informasi Profil</h2><span class="badge">Edit</span></div>
      <form method="post" enctype="multipart/form-data">
        <?=csrf_input()?><input type="hidden" name="action" value="profile">
        <div class="form-grid">
          <div class="form-group"><label>Nama Lengkap</label><input class="input" name="name" required value="<?=e($u['name'])?>"></div>
          <div class="form-group"><label>NIM</label><input class="input" value="<?=e($u['nim']??'—')?>" disabled></div>
          <div class="form-group"><label>Email</label><input class="input" value="<?=e($u['email'])?>" disabled></div>
          <div class="form-group"><label>Nomor HP</label><input class="input" name="phone" value="<?=e($u['phone']??'')?>"></div>
          <div class="form-group"><label>Kelas</label><input class="input" name="class_name" value="<?=e($u['class_name']??'')?>"></div>
          <div class="form-group"><label>Program Studi</label><input class="input" name="study_program" required value="<?=e($u['study_program']??'')?>"></div>
          <div class="form-group"><label>Fakultas</label><input class="input" name="faculty" value="<?=e($u['faculty']??'')?>"></div>
          <div class="form-group"><label>Foto Profil</label><input class="input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <button class="btn btn-primary" style="margin-top:16px">Simpan Profil</button>
      </form>
    </section>
    <section class="card" id="ubah-password">
      <div class="profile-section-title"><h2 style="margin:0">Ubah Password</h2><span class="badge">Keamanan</span></div>
      <form method="post">
        <?=csrf_input()?><input type="hidden" name="action" value="password">
        <div class="form-group"><label>Password Lama</label><input class="input" type="password" name="old_password" required autocomplete="current-password"></div>
        <div class="form-group" style="margin-top:12px"><label>Password Baru</label><input class="input" type="password" name="new_password" required minlength="8" autocomplete="new-password"></div>
        <div class="form-group" style="margin-top:12px"><label>Konfirmasi Password Baru</label><input class="input" type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></div>
        <button class="btn btn-dark" style="margin-top:16px">Simpan Password</button>
      </form>
    </section>
  </div>

  <div class="grid grid-2">
    <section class="card"><div class="profile-section-title"><h2 style="margin:0">Informasi Akun</h2></div>
      <div class="profile-info-grid">
        <div class="detail-item"><small>Username / NIM</small><b><?=e($u['nim']??'—')?></b></div>
        <div class="detail-item"><small>Role</small><b>Pengguna</b></div>
        <div class="detail-item"><small>Tanggal Bergabung</small><b><?=e($u['created_at']??'—')?></b></div>
        <div class="detail-item"><small>Login Terakhir</small><b><?=e($u['last_login_at']??'Belum tercatat')?></b></div>
      </div>
    </section>
    <section class="card"><div class="profile-section-title"><h2 style="margin:0">Aktivitas Akun</h2></div>
      <div style="display:grid;gap:10px"><?php foreach($activities as $a):?><div class="detail-item"><b><?=e($a['action'])?></b><small><?=e($a['description']??'')?></small><small><?=e($a['created_at'])?></small></div><?php endforeach;if(!$activities):?><div class="empty">Belum ada aktivitas tercatat.</div><?php endif;?></div>
    </section>
  </div>
</div>
<?php render_end(); ?>
