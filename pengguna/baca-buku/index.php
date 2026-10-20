<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_login();
$navRole=dashboard_role($u);
$id=safe_int($_GET['id']??0);

$sql="SELECT title,file_path,approval_status FROM books WHERE id=? AND file_path IS NOT NULL";
if($navRole==='pengguna'){
    $sql.=" AND approval_status='disetujui'";
}
$sql.=" LIMIT 1";

$st=$pdo->prepare($sql);
$st->execute([$id]);
$b=$st->fetch();
if(!$b){
    http_response_code(404);
    exit('File buku digital tidak tersedia atau belum dapat diakses.');
}

require __DIR__.'/../../app/helpers/layout.php';
render_start('Baca Buku','Pembaca dokumen digital',$u);
dashboard_nav($navRole);
render_mid('Baca Buku', $b['title'], $u);
?>
<div class="card">
  <div class="toolbar">
    <div>
      <b><?=e($b['title'])?></b>
      <div class="muted">Gunakan kontrol PDF bawaan browser untuk halaman, zoom, dan mode layar penuh.</div>
    </div>
    <div class="inline-form">
      <a class="btn btn-light" href="/pengguna/detail-buku/index.php?id=<?=$id?>">Kembali ke Detail</a>
      <a class="btn btn-primary" target="_blank" rel="noopener" href="<?=e($b['file_path'])?>">Buka Fullscreen</a>
    </div>
  </div>
  <div class="pdf-wrap" style="margin-top:16px">
    <iframe class="pdf-viewer" src="<?=e($b['file_path'])?>#page=1" title="PDF Buku" loading="lazy"></iframe>
  </div>
</div>
<?php render_end(); ?>
