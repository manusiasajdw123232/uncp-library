<?php
require __DIR__.'/../../app/core/bootstrap.php'; $u=require_roles(['penjaga']); require_once __DIR__.'/../../app/helpers/library.php'; require __DIR__.'/../../app/helpers/layout.php';
$error=null;
if(is_post()){
  csrf_check(); $action=$_POST['action']??''; $id=safe_int($_POST['id']??0);
  try{
    if($action==='delete'){
      $q=$pdo->prepare('SELECT cover,file_path,title FROM repositories WHERE id=? AND type="skripsi"');$q->execute([$id]);$old=$q->fetch();
      if(!$old) throw new RuntimeException('Data tidak ditemukan.');
      $pdo->prepare('DELETE FROM repositories WHERE id=? AND type="skripsi"')->execute([$id]);
      delete_local_upload($old['cover']??null); delete_local_upload($old['file_path']??null);
      log_activity($pdo,safe_int($u['id']),$u['role_name'],'Hapus skripsi','#'.$id.' '.$old['title']);
      flash('success','Skripsi berhasil dihapus.');
    } elseif(in_array($action,['create','update'],true)) {
      $title=trim($_POST['title']??'');$author=trim($_POST['author']??'');$nim=trim($_POST['nim']??'');$program=trim($_POST['study_program']??'');
      $year=trim($_POST['publication_year']??'');$advisor=trim($_POST['advisor']??'');$abstract=trim($_POST['abstract']??'');$keywords=trim($_POST['keywords']??'');
      if(!$title||!$author||!$nim||!$program) throw new RuntimeException('Judul, mahasiswa, NIM, dan program studi wajib diisi.');
      $old=null;
      if($action==='update'){$q=$pdo->prepare('SELECT * FROM repositories WHERE id=? AND type="skripsi"');$q->execute([$id]);$old=$q->fetch();if(!$old)throw new RuntimeException('Data skripsi tidak ditemukan.');}
      $cover=save_upload($_FILES['cover']??[],'uploads/repository',['jpg','jpeg','png','webp'],5*1024*1024) ?? ($old['cover']??null);
      $pdf=save_upload($_FILES['pdf']??[],'uploads/repository',['pdf'],20*1024*1024) ?? ($old['file_path']??null);
      if($action==='create'){
        $st=$pdo->prepare("INSERT INTO repositories(user_id,title,author,nim,study_program,publication_year,advisor,type,abstract,keywords,file_path,cover,status) VALUES(?,?,?,?,?,?,?,'skripsi',?,?,?,?, 'publik')");
        $st->execute([safe_int($u['id']),$title,$author,$nim,$program,$year?:null,$advisor,$abstract,$keywords,$pdf,$cover]);
        $newId=safe_int($pdo->lastInsertId());log_activity($pdo,safe_int($u['id']),$u['role_name'],'Tambah skripsi','#'.$newId.' '.$title);flash('success','Skripsi berhasil ditambahkan.');
      } else {
        $pdo->prepare('UPDATE repositories SET title=?,author=?,nim=?,study_program=?,publication_year=?,advisor=?,abstract=?,keywords=?,file_path=?,cover=?,status="publik" WHERE id=? AND type="skripsi"')->execute([$title,$author,$nim,$program,$year?:null,$advisor,$abstract,$keywords,$pdf,$cover,$id]);
        if(($cover??null)!==($old['cover']??null)) delete_local_upload($old['cover']??null);
        if(($pdf??null)!==($old['file_path']??null)) delete_local_upload($old['file_path']??null);
        log_activity($pdo,safe_int($u['id']),$u['role_name'],'Edit skripsi','#'.$id.' '.$title);flash('success','Skripsi berhasil diperbarui.');
      }
    }
    if(!$error) redirect('/penjaga/repository/index.php');
  }catch(Throwable $e){$error='Perubahan data skripsi gagal. Periksa input dan file yang diunggah.';}
}
$q=trim($_GET['q']??'');$params=[];$sql="SELECT r.*,u.name owner_name FROM repositories r LEFT JOIN users u ON u.id=r.user_id WHERE r.type='skripsi'";
if($q!==''){$sql.=' AND (r.title LIKE ? OR r.author LIKE ? OR r.nim LIKE ? OR r.study_program LIKE ?)';$like='%'.$q.'%';$params=[$like,$like,$like,$like];}
$sql.=' ORDER BY r.id DESC LIMIT 300';$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll();
$detailUrl='/pengguna/detail-skripsi/index.php';
render_start('Katalog Skripsi','Kelola repository skripsi',$u);dashboard_nav('penjaga');render_mid('Katalog Skripsi','Tambah, edit, hapus, dan buka detail skripsi',$u);
if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif; ?>
<div class="card"><div class="toolbar"><div><b>Kelola Skripsi</b><p class="muted">Data yang dimasukkan langsung oleh staf berstatus Disetujui.</p></div><button type="button" class="btn btn-primary" data-open-repo-modal>+ Tambah Skripsi</button></div>
<form method="get" class="data-toolbar" style="margin-top:16px"><input class="input" name="q" value="<?=e($q)?>" placeholder="Cari judul, mahasiswa, NIM, prodi..."><button class="btn btn-light">Cari</button><a class="btn btn-light" href="/penjaga/repository/index.php">Reset</a><span class="badge">Total <?=count($rows)?> data</span></form></div>
<div class="card" style="margin-top:16px"><div class="table-wrap"><table class="table"><thead><tr><th>Judul</th><th>Mahasiswa</th><th>NIM</th><th>Prodi</th><th>Tahun</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><b><?=e($r['title'])?></b><div class="muted"><?=e($r['advisor']??'Pembimbing belum diisi')?></div></td><td><?=e($r['author'])?></td><td><?=e($r['nim']??'—')?></td><td><?=e($r['study_program']??'—')?></td><td><?=e($r['publication_year']??'—')?></td><td><span class="badge <?=e(status_class($r['status']))?>"><?=e(status_label($r['status']))?></span></td><td><div class="inline-form"><a class="btn btn-light" href="<?=e($detailUrl)?>?id=<?=$r['id']?>">Detail</a><?php if($r['file_path']):?><a class="btn btn-light" href="<?=e($r['file_path'])?>" target="_blank" rel="noopener">PDF</a><?php endif;?><button type="button" class="btn btn-light" data-edit-repo='<?=e(json_encode([
    'id'=>$r['id'],'title'=>$r['title'],'author'=>$r['author'],'nim'=>$r['nim']??'',
    'study_program'=>$r['study_program']??'','publication_year'=>$r['publication_year']??'',
    'advisor'=>$r['advisor']??'','abstract'=>$r['abstract']??'','keywords'=>$r['keywords']??''
],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_TAG))?>'>Edit</button><form method="post" class="inline-form"><?=csrf_input()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-danger" type="submit" data-confirm="Hapus data skripsi ini? Data yang telah dihapus tidak dapat dikembalikan.">Hapus</button></form></div></td></tr><?php endforeach;if(!$rows):?><tr><td colspan="7" class="empty">Belum ada data skripsi.</td></tr><?php endif;?></tbody></table></div></div>
<div class="modal-backdrop" id="repoModal" aria-hidden="true"><div class="confirm-modal modal-shell"><div class="profile-section-title"><div><h2 id="repoModalTitle" style="margin:0">Tambah Skripsi</h2><p class="muted">Masukkan metadata karya ilmiah.</p></div><button type="button" class="btn btn-light" data-close-repo-modal>×</button></div>
<form method="post" enctype="multipart/form-data" id="repoForm"><?=csrf_input()?><input type="hidden" name="action" id="repoAction" value="create"><input type="hidden" name="id" id="repoId"><div class="form-grid"><div class="form-group"><label>Judul</label><input class="input" id="repoTitle" name="title" required></div><div class="form-group"><label>Nama Mahasiswa</label><input class="input" id="repoAuthor" name="author" required></div><div class="form-group"><label>NIM</label><input class="input" id="repoNim" name="nim" required></div><div class="form-group"><label>Program Studi</label><input class="input" id="repoProgram" name="study_program" required></div><div class="form-group"><label>Tahun</label><input class="input" id="repoYear" name="publication_year" type="number" min="2000" max="2100"></div><div class="form-group"><label>Dosen Pembimbing</label><input class="input" id="repoAdvisor" name="advisor"></div></div>
<div class="form-group" style="margin-top:14px"><label>Abstrak</label><textarea class="input" id="repoAbstract" name="abstract" rows="7"></textarea></div><div class="form-group" style="margin-top:14px"><label>Kata Kunci</label><input class="input" id="repoKeywords" name="keywords"></div><div class="form-grid" style="margin-top:14px"><div class="form-group"><label>Cover</label><input class="input" type="file" name="cover" accept="image/jpeg,image/png,image/webp"></div><div class="form-group"><label>File PDF</label><input class="input" type="file" name="pdf" accept="application/pdf"></div></div><div class="form-actions"><button type="button" class="btn btn-light" data-close-repo-modal>Batal</button><button class="btn btn-primary">Simpan Skripsi</button></div></form></div></div>
<script>
(function(){
  const m=document.getElementById('repoModal'),f=document.getElementById('repoForm');
  const set=(id,v)=>{const e=document.getElementById(id);if(e)e.value=v??'';};
  function open(d){
    window.captureModalScroll?.();
    m.classList.add('is-open');m.setAttribute('aria-hidden','false');window.lockPageScroll?.();
    if(d){
      document.getElementById('repoModalTitle').textContent='Edit Skripsi';
      set('repoAction','update');set('repoId',d.id);set('repoTitle',d.title);set('repoAuthor',d.author);
      set('repoNim',d.nim);set('repoProgram',d.study_program);set('repoYear',d.publication_year);
      set('repoAdvisor',d.advisor);set('repoAbstract',d.abstract);set('repoKeywords',d.keywords);
    }else{
      document.getElementById('repoModalTitle').textContent='Tambah Skripsi';
      f.reset();set('repoAction','create');
    }
  }
  function close(){
    m.classList.remove('is-open');m.setAttribute('aria-hidden','true');window.unlockPageScroll?.();
    window.restoreModalScroll?.();
  }
  document.querySelector('[data-open-repo-modal]')?.addEventListener('click',()=>open());
  document.querySelectorAll('[data-close-repo-modal]').forEach(b=>b.addEventListener('click',close));
  document.querySelectorAll('[data-edit-repo]').forEach(b=>b.addEventListener('click',()=>{try{open(JSON.parse(b.dataset.editRepo));}catch(e){}}));
  m.addEventListener('click',e=>{if(e.target===m)close();});
})();
</script>
<?php render_end(); ?>
