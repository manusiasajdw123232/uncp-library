<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_login();
$navRole=dashboard_role($u);
$id=safe_int($_GET['id']??0);

$sql="SELECT r.*,u.name owner_name
      FROM repositories r
      LEFT JOIN users u ON u.id=r.user_id
      WHERE r.id=? AND r.type='skripsi'";
$params=[$id];

if($navRole==='pengguna'){
    $sql.=" AND r.status='publik'";
}
$sql.=" LIMIT 1";

$st=$pdo->prepare($sql);
$st->execute($params);
$r=$st->fetch();

if(!$r){
    http_response_code(404);
    exit('Skripsi tidak ditemukan atau belum dapat diakses.');
}

require __DIR__.'/../../app/helpers/layout.php';
render_start('Detail Skripsi','Informasi lengkap karya ilmiah',$u);
dashboard_nav($navRole);
render_mid('Detail Skripsi','Karya ilmiah #'.$id,$u);

$status=$r['status']??'publik';
$statusClass=status_class($status);
$statusLabel=status_label($status);
?>
<div class="card">
  <span class="badge <?=e($statusClass)?>"><?=e($statusLabel)?></span>
  <h1><?=e($r['title'])?></h1>
  <p class="muted"><?=e($r['author']??'—')?> · NIM <?=e($r['nim']??'—')?></p>

  <div class="detail-list" style="margin-top:16px">
    <div class="detail-item"><small>Nama Mahasiswa</small><b><?=e($r['author']??'—')?></b></div>
    <div class="detail-item"><small>NIM</small><b><?=e($r['nim']??'—')?></b></div>
    <div class="detail-item"><small>Program Studi</small><b><?=e($r['study_program']??'—')?></b></div>
    <div class="detail-item"><small>Tahun</small><b><?=e($r['publication_year']??'—')?></b></div>
    <div class="detail-item"><small>Dosen Pembimbing</small><b><?=e($r['advisor']??'—')?></b></div>
    <div class="detail-item"><small>Kata Kunci</small><b><?=e($r['keywords']??'—')?></b></div>
  </div>

  <h3>Abstrak</h3>
  <p class="muted" style="line-height:1.8"><?=nl2br(e($r['abstract']??'Belum ada abstrak.'))?></p>

  <?php if(!empty($r['file_path'])): ?>
    <a class="btn btn-primary min-touch" href="/pengguna/baca-skripsi/index.php?id=<?=$r['id']?>">Baca Skripsi</a>
  <?php else: ?>
    <div class="alert" style="margin-top:12px">File skripsi digital belum tersedia.</div>
  <?php endif; ?>

  <?php if($navRole!=='pengguna' && !empty($r['rejection_reason'])): ?>
    <div class="detail-item" style="margin-top:14px"><small>Catatan Verifikasi</small><b><?=nl2br(e($r['rejection_reason']))?></b></div>
  <?php endif; ?>
</div>
<?php render_end(); ?>
