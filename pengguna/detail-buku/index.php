<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_login();
$navRole=dashboard_role($u);
$id=safe_int($_GET['id']??0);

$sql="SELECT b.*,c.name category,p.name publisher
      FROM books b
      LEFT JOIN book_categories c ON c.id=b.category_id
      LEFT JOIN publishers p ON p.id=b.publisher_id
      WHERE b.id=?";
$params=[$id];

if($navRole==='pengguna'){
    $sql.=" AND b.approval_status='disetujui'";
}
$sql.=" LIMIT 1";

$st=$pdo->prepare($sql);
$st->execute($params);
$b=$st->fetch();

if(!$b){
    http_response_code(404);
    exit('Buku tidak ditemukan atau belum dapat diakses.');
}

require __DIR__.'/../../app/helpers/layout.php';
render_start('Detail Buku','Informasi lengkap koleksi',$u);
dashboard_nav($navRole);
render_mid('Detail Buku','Koleksi #'.$id,$u);

$approval=$b['approval_status']??'disetujui';
$statusClass=status_class($approval);
$statusLabel=status_label($approval);
?>
<div class="card">
  <div class="grid grid-2">
    <div>
      <?php if(!empty($b['cover'])): ?>
        <img class="book-cover detail-book-cover" src="<?=e($b['cover'])?>" alt="Cover <?=e($b['title'])?>">
      <?php else: ?>
        <div class="cover-placeholder detail-book-cover" aria-label="Cover tidak tersedia">UNCP<br>LIBRARY</div>
      <?php endif; ?>
    </div>

    <div>
      <span class="badge <?=e($statusClass)?>"><?=e($statusLabel)?></span>
      <h1><?=e($b['title'])?></h1>
      <p class="muted">oleh <?=e($b['author']??'—')?></p>

      <div class="detail-list">
        <div class="detail-item"><small>Penerbit</small><b><?=e($b['publisher']??'—')?></b></div>
        <div class="detail-item"><small>Tahun Terbit</small><b><?=e($b['publication_year']??'—')?></b></div>
        <div class="detail-item"><small>ISBN</small><b><?=e($b['isbn']??'—')?></b></div>
        <div class="detail-item"><small>Kategori</small><b><?=e($b['category']??'—')?></b></div>
        <div class="detail-item"><small>Jumlah Halaman</small><b><?=e($b['pages']??'—')?></b></div>
        <div class="detail-item"><small>Bahasa</small><b><?=e($b['language']??'—')?></b></div>
        <div class="detail-item"><small>Lokasi Rak</small><b><?=e($b['shelf_location']??'—')?></b></div>
        <div class="detail-item"><small>Status Koleksi</small><b><?=e(book_status(safe_int($b['stock_total']),safe_int($b['stock_available']),$b['status']??''))?></b></div>
      </div>

      <h3 style="margin-top:22px">Deskripsi</h3>
      <p class="muted" style="line-height:1.7"><?=nl2br(e($b['description']??'Belum ada deskripsi.'))?></p>

      <?php if(!empty($b['file_path'])): ?>
        <a class="btn btn-primary min-touch" href="/pengguna/baca-buku/index.php?id=<?=$b['id']?>">Baca Buku</a>
      <?php elseif($navRole==='pengguna'): ?>
        <div class="alert" style="margin-top:12px">Isi buku digital belum tersedia.</div>
      <?php else: ?>
        <div class="alert" style="margin-top:12px">File buku digital belum tersedia.</div>
      <?php endif; ?>

      <?php if($navRole!=='pengguna' && !empty($b['rejection_reason'])): ?>
        <div class="detail-item" style="margin-top:14px"><small>Catatan Verifikasi</small><b><?=nl2br(e($b['rejection_reason']))?></b></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php render_end(); ?>
