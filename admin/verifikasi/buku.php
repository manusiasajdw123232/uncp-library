<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_admin();

if(is_post()){
    csrf_check();
    $id=safe_int($_POST['id']??0);
    $action=$_POST['decision']??'';
    $reason=trim($_POST['reason']??'');

    if(!in_array($action,['disetujui','ditolak','perlu_perbaikan'],true)){
        flash('error','Aksi verifikasi tidak valid.');
        redirect('/admin/verifikasi/buku.php');
    }

    if(in_array($action,['ditolak','perlu_perbaikan'],true) && $reason===''){
        flash('error','Alasan wajib diisi untuk penolakan atau permintaan perbaikan.');
        redirect('/admin/verifikasi/buku.php');
    }

    $st=$pdo->prepare("SELECT b.*,u.name owner_name,u.id owner_id
                       FROM books b
                       LEFT JOIN users u ON u.id=b.submitted_by
                       WHERE b.id=? LIMIT 1");
    $st->execute([$id]);
    $b=$st->fetch();

    if(!$b){
        flash('error','Data buku tidak ditemukan.');
        redirect('/admin/verifikasi/buku.php');
    }

    $pdo->prepare('UPDATE books SET approval_status=?,rejection_reason=? WHERE id=?')
        ->execute([$action,$reason!==''?$reason:null,$id]);

    submission_history(
        $pdo,
        $b['owner_id']!==null?safe_int($b['owner_id']):null,
        'buku',
        $id,
        $action,
        $reason!==''?$reason:null,
        safe_int($u['id'])
    );

    if($b['owner_id']!==null){
        $notifyTitle=$action==='disetujui'?'Buku disetujui':($action==='ditolak'?'Buku ditolak':'Buku perlu diperbaiki');
        $notifyMessage=$action==='disetujui'
            ? 'Pengajuan buku "'.($b['title']??'').'" telah disetujui.'
            : ($action==='ditolak'
                ? 'Pengajuan buku "'.($b['title']??'').'" ditolak. Alasan: '.($reason!==''?$reason:'Tidak ada alasan yang dicatat.')
                : 'Pengajuan buku "'.($b['title']??'').'" memerlukan perbaikan. Catatan: '.($reason!==''?$reason:'Silakan periksa kembali data.'));
        notify_user($pdo,safe_int($b['owner_id']),$notifyTitle,$notifyMessage);
    }

    log_activity($pdo,safe_int($u['id']),$u['role_name'],'Verifikasi buku','#'.$id.' -> '.$action);
    flash('success','Status buku berhasil diperbarui.');
    redirect('/admin/verifikasi/buku.php');
}

require __DIR__.'/../../app/helpers/layout.php';

$status=$_GET['status']??'all';
$q=trim($_GET['q']??'');
$page=max(1,safe_int($_GET['page']??1,1));
$allowedStatus=['all','menunggu','disetujui','ditolak','perlu_perbaikan'];
if(!in_array($status,$allowedStatus,true)) $status='all';

$where=['1=1'];
$params=[];
if($q!==''){
    $where[]='(b.title LIKE ? OR b.author LIKE ? OR u.name LIKE ? OR b.isbn LIKE ? OR c.name LIKE ?)';
    $like='%'.$q.'%';
    array_push($params,$like,$like,$like,$like,$like);
}
if($status!=='all'){
    $where[]='b.approval_status=?';
    $params[]=$status;
}

$fromSql="FROM books b
          LEFT JOIN users u ON u.id=b.submitted_by
          LEFT JOIN book_categories c ON c.id=b.category_id
          LEFT JOIN publishers p ON p.id=b.publisher_id
          WHERE ".implode(' AND ',$where);

$countSt=$pdo->prepare('SELECT COUNT(*) '.$fromSql);
$countSt->execute($params);
$totalRows=safe_int($countSt->fetchColumn());
$perPage=20;
$totalPages=max(1,(int)ceil($totalRows/$perPage));
if($page>$totalPages) $page=$totalPages;
$offset=($page-1)*$perPage;

$sql="SELECT b.*,u.name owner_name,u.nim owner_nim,c.name category,p.name publisher
      ".$fromSql."
      ORDER BY b.id DESC
      LIMIT ".$perPage." OFFSET ".$offset;
$st=$pdo->prepare($sql);
$st->execute($params);
$rows=$st->fetchAll();

$baseQuery=['q'=>$q,'status'=>$status];
function verify_book_page_url(int $page,array $baseQuery,string $route): string {
    return $route.'?'.http_build_query(array_merge($baseQuery,['page'=>$page]));
}

render_start('Verifikasi Buku','Periksa dan proses pengajuan buku',$u);
dashboard_nav('admin');
render_mid('Verifikasi Buku','Cari, filter, lihat detail, dan proses pengajuan',$u);
?>
<div class="card">
  <form method="get" class="toolbar">
    <input class="input search" name="q" value="<?=e($q)?>" placeholder="Cari judul, penulis, pengirim, ISBN, kategori..." aria-label="Cari pengajuan buku">
    <select name="status" class="input" style="max-width:220px" aria-label="Filter status">
      <?php foreach(['all'=>'Semua Status','menunggu'=>'Menunggu','disetujui'=>'Disetujui','ditolak'=>'Ditolak','perlu_perbaikan'=>'Perlu Perbaikan'] as $k=>$v):?>
        <option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option>
      <?php endforeach;?>
    </select>
    <button class="btn btn-primary" type="submit">Terapkan</button>
    <a class="btn btn-light" href="<?=e('/admin/verifikasi/buku.php')?>">Reset</a>
  </form>
</div>

<div class="card" style="margin-top:16px">
  <div class="toolbar" style="margin-bottom:10px">
    <div><b><?=e($totalRows)?> pengajuan</b><div class="muted">Halaman <?=e($page)?> dari <?=e($totalPages)?></div></div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cover</th><th>Informasi Buku</th><th>Pengirim</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r):?>
      <tr>
        <td>
          <?php if(!empty($r['cover'])):?>
            <img src="<?=e($r['cover'])?>" alt="Cover buku" class="table-thumb">
          <?php else:?>
            <div class="cover-placeholder table-thumb" style="aspect-ratio:auto;font-size:9px;padding:4px">UNCP</div>
          <?php endif;?>
        </td>
        <td>
          <b><?=e($r['title'])?></b>
          <div class="muted"><?=e($r['author'])?></div>
          <details style="margin-top:6px">
            <summary>Ringkasan detail</summary>
            <div class="text-content" style="margin-top:7px">
              Penerbit: <?=e($r['publisher']??'—')?> · Tahun: <?=e($r['publication_year']??'—')?> · ISBN: <?=e($r['isbn']??'—')?> · Kategori: <?=e($r['category']??'—')?>
            </div>
          </details>
        </td>
        <td><?=e($r['owner_name']??'—')?><div class="muted">NIM <?=e($r['owner_nim']??'—')?></div></td>
        <td><?=e($r['submitted_at']??$r['created_at']??'—')?></td>
        <td>
          <span class="badge <?=e(status_class($r['approval_status']))?>"><?=e(status_label($r['approval_status']))?></span>
          <?php if(!empty($r['rejection_reason'])):?><div class="muted" style="margin-top:6px"><?=e($r['rejection_reason'])?></div><?php endif;?>
        </td>
        <td>
          <div class="inline-form">
            <a class="btn btn-light" href="<?=e('/pengguna/detail-buku/index.php')?>?id=<?=$r['id']?>">Detail</a>
            <?php if(!empty($r['file_path'])):?><a class="btn btn-light" href="<?=e($r['file_path'])?>" target="_blank" rel="noopener">PDF</a><?php endif;?>
            <form method="post" class="inline-form" data-verify-form>
              <?=csrf_input()?>
              <input type="hidden" name="id" value="<?=$r['id']?>">
              <input type="hidden" name="reason" value="">
              <button class="btn btn-primary" name="decision" value="disetujui">Terima</button>
              <button type="button" class="btn btn-danger" data-verify-decision="ditolak" data-verify-label="Tolak">Tolak</button>
              <button type="button" class="btn btn-light" data-verify-decision="perlu_perbaikan" data-verify-label="Perlu Perbaikan">Perbaikan</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach;?>
      <?php if(!$rows):?><tr><td colspan="6" class="empty">Tidak ada pengajuan buku untuk filter ini.</td></tr><?php endif;?>
      </tbody>
    </table>
  </div>
  <?php if($totalPages>1):?>
  <nav class="pagination" aria-label="Pagination">
    <?php if($page>1):?><a class="btn btn-light" href="<?=e(verify_book_page_url($page-1,$baseQuery,'/admin/verifikasi/buku.php'))?>">Sebelumnya</a><?php endif;?>
    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++):?>
      <a class="btn <?=$i===$page?'btn-primary':'btn-light'?>" href="<?=e(verify_book_page_url($i,$baseQuery,'/admin/verifikasi/buku.php'))?>" aria-current="<?=$i===$page?'page':'false'?>"><?=$i?></a>
    <?php endfor;?>
    <?php if($page<$totalPages):?><a class="btn btn-light" href="<?=e(verify_book_page_url($page+1,$baseQuery,'/admin/verifikasi/buku.php'))?>">Berikutnya</a><?php endif;?>
  </nav>
  <?php endif;?>
</div>

<div class="modal-backdrop" id="verifyReasonModal" aria-hidden="true">
  <div class="confirm-modal modal-shell" role="dialog" aria-modal="true" aria-labelledby="verifyReasonTitle">
    <div class="profile-section-title">
      <div><h2 id="verifyReasonTitle" style="margin:0">Alasan Verifikasi</h2><p class="muted" id="verifyReasonHelp">Tambahkan catatan untuk pengaju.</p></div>
      <button type="button" class="btn btn-light" id="verifyReasonClose" aria-label="Tutup">×</button>
    </div>
    <div class="form-group">
      <label for="verifyReasonText">Alasan / Catatan</label>
      <textarea class="input" id="verifyReasonText" rows="5" placeholder="Tuliskan alasan atau catatan yang perlu diketahui pengaju."></textarea>
    </div>
    <div class="form-actions">
      <button type="button" class="btn btn-light" id="verifyReasonCancel">Batal</button>
      <button type="button" class="btn btn-primary" id="verifyReasonSubmit">Simpan & Proses</button>
    </div>
  </div>
</div>

<script>
(function(){
  const modal=document.getElementById('verifyReasonModal');
  const text=document.getElementById('verifyReasonText');
  const title=document.getElementById('verifyReasonTitle');
  const help=document.getElementById('verifyReasonHelp');
  const closeBtn=document.getElementById('verifyReasonClose');
  const cancelBtn=document.getElementById('verifyReasonCancel');
  const submitBtn=document.getElementById('verifyReasonSubmit');
  let currentForm=null;
  let currentDecision='';

  function openReason(form,decision,label){
    currentForm=form;currentDecision=decision;
    title.textContent=label+' Pengajuan Buku';
    help.textContent=decision==='ditolak'?'Jelaskan alasan penolakan kepada pengaju.':'Jelaskan bagian yang perlu diperbaiki kepada pengaju.';
    text.value='';
    window.captureModalScroll?.();
    modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');window.lockPageScroll?.();
    setTimeout(()=>text.focus(),0);
  }
  function closeReason(){
    modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');window.unlockPageScroll?.();
    currentForm=null;currentDecision='';text.value='';
    window.restoreModalScroll?.();
  }

  document.querySelectorAll('[data-verify-form]').forEach(function(form){
    form.addEventListener('click',function(e){
      const button=e.target.closest('[data-verify-decision]');
      if(!button)return;
      e.preventDefault();
      openReason(form,button.dataset.verifyDecision||'',button.dataset.verifyLabel||'Proses');
    });
  });

  submitBtn.addEventListener('click',function(){
    const reason=text.value.trim();
    if(!currentForm || !currentDecision)return;
    if(!reason){
      text.focus();
      text.setCustomValidity('Alasan wajib diisi.');
      text.reportValidity();
      text.setCustomValidity('');
      return;
    }
    const hidden=currentForm.querySelector('input[name="reason"]');
    if(hidden)hidden.value=reason;
    const decision=currentForm.querySelector('input[name="decision"][type="hidden"]');
    if(decision)decision.value=currentDecision;
    else{
      const h=document.createElement('input');h.type='hidden';h.name='decision';h.value=currentDecision;currentForm.appendChild(h);
    }
    currentForm.submit();
  });

  [closeBtn,cancelBtn].forEach(b=>b.addEventListener('click',closeReason));
  modal.addEventListener('click',e=>{if(e.target===modal)closeReason();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape' && modal.classList.contains('is-open'))closeReason();});
})();
</script>
<?php render_end(); ?>
