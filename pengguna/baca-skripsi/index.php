<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_login();
$navRole=dashboard_role($u);
$id=safe_int($_GET['id']??0);

$sql="SELECT title,file_path,status FROM repositories WHERE id=? AND type='skripsi' AND file_path IS NOT NULL";
if($navRole==='pengguna'){
    $sql.=" AND status='publik'";
}
$sql.=" LIMIT 1";

$st=$pdo->prepare($sql);
$st->execute([$id]);
$r=$st->fetch();
if(!$r){
    http_response_code(404);
    exit('File skripsi tidak tersedia atau belum dapat diakses.');
}

require __DIR__.'/../../app/helpers/layout.php';
render_start('Baca Skripsi','Pembaca dokumen digital',$u);
dashboard_nav($navRole);
render_mid('Baca Skripsi', $r['title'], $u);
?>
<div class="card">
  <div class="toolbar">
    <div>
      <b><?=e($r['title'])?></b>
      <div class="muted">Gunakan kontrol PDF bawaan browser untuk halaman, zoom, dan mode layar penuh.</div>
    </div>
    <div class="inline-form">
      <a class="btn btn-light" href="/pengguna/detail-skripsi/index.php?id=<?=$id?>">Kembali ke Detail</a>
      <a class="btn btn-primary" target="_blank" rel="noopener" href="<?=e($r['file_path'])?>">Buka Fullscreen</a>
    </div>
  </div>
  <div class="pdf-wrap" style="margin-top:16px">
    <iframe class="pdf-viewer" src="<?=e($r['file_path'])?>#page=1" title="PDF Skripsi" loading="lazy"></iframe>
  </div>
</div>
<?php render_end(); ?>
