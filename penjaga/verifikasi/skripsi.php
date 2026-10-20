<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_roles(['penjaga']);

if(is_post()){
    csrf_check();
    $id=safe_int($_POST['id']??0);
    $action=$_POST['decision']??'';
    $reason=trim($_POST['reason']??'');

    if(!in_array($action,['publik','ditolak','perlu_perbaikan'],true)){
        flash('error','Aksi verifikasi tidak valid.');
        redirect('/penjaga/verifikasi/skripsi.php');
    }
    if(in_array($action,['ditolak','perlu_perbaikan'],true) && $reason===''){
        flash('error','Alasan wajib diisi untuk penolakan atau permintaan perbaikan.');
        redirect('/penjaga/verifikasi/skripsi.php');
    }

    $st=$pdo->prepare("SELECT r.*,u.name owner_name,u.id owner_id
                       FROM repositories r
                       LEFT JOIN users u ON u.id=r.user_id
                       WHERE r.id=? AND r.type='skripsi' LIMIT 1");
    $st->execute([$id]);
    $r=$st->fetch();

    if(!$r){
        flash('error','Data skripsi tidak ditemukan.');
        redirect('/penjaga/verifikasi/skripsi.php');
    }

    $pdo->prepare('UPDATE repositories SET status=?,rejection_reason=? WHERE id=?')
        ->execute([$action,$reason!==''?$reason:null,$id]);

    $histAction=$action==='publik'?'disetujui':$action;
    submission_history(
        $pdo,
        $r['owner_id']!==null?safe_int($r['owner_id']):null,
        'skripsi',
        $id,
        $histAction,
        $reason!==''?$reason:null,
        safe_int($u['id'])
    );

    if($r['owner_id']!==null){
        $notifyTitle=$action==='publik'?'Skripsi disetujui':($action==='ditolak'?'Skripsi ditolak':'Skripsi perlu diperbaiki');
        $notifyMessage=$action==='publik'
            ? 'Pengajuan skripsi "'.($r['title']??'').'" telah disetujui.'
            : ($action==='ditolak'
                ? 'Pengajuan skripsi "'.($r['title']??'').'" ditolak. Alasan: '.($reason!==''?$reason:'Tidak ada alasan yang dicatat.')
                : 'Pengajuan skripsi "'.($r['title']??'').'" memerlukan perbaikan. Catatan: '.($reason!==''?$reason:'Silakan periksa kembali data.'));
        notify_user($pdo,safe_int($r['owner_id']),$notifyTitle,$notifyMessage);
    }

    log_activity($pdo,safe_int($u['id']),$u['role_name'],'Verifikasi skripsi','#'.$id.' -> '.$action);
    flash('success','Status skripsi berhasil diperbarui.');
    redirect('/penjaga/verifikasi/skripsi.php');
}

require __DIR__.'/../../app/helpers/layout.php';

$status=$_GET['status']??'all';
$q=trim($_GET['q']??'');
$page=max(1,safe_int($_GET['page']??1,1));
$allowedStatus=['all','publik','menunggu','ditolak','perlu_perbaikan'];
if(!in_array($status,$allowedStatus,true)) $status='all';

$where=["r.type='skripsi'"];
$params=[];
if($q!==''){
    $where[]='(r.title LIKE ? OR r.author LIKE ? OR r.nim LIKE ? OR r.study_program LIKE ? OR u.name LIKE ?)';
    $like='%'.$q.'%';
    array_push($params,$like,$like,$like,$like,$like);
}
if($status!=='all'){
    $where[]='r.status=?';
    $params[]=$status;
}

$fromSql="FROM repositories r
          LEFT JOIN users u ON u.id=r.user_id
          WHERE ".implode(' AND ',$where);

$countSt=$pdo->prepare('SELECT COUNT(*) '.$fromSql);
$countSt->execute($params);
$totalRows=safe_int($countSt->fetchColumn());
$perPage=20;
$totalPages=max(1,(int)ceil($totalRows/$perPage));
if($page>$totalPages) $page=$totalPages;
$offset=($page-1)*$perPage;

$sql="SELECT r.*,u.name owner_name
      ".$fromSql."
      ORDER BY r.id DESC
      LIMIT ".$perPage." OFFSET ".$offset;
$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll();
$baseQuery=['q'=>$q,'status'=>$status];
function verify_thesis_page_url(int $page,array $baseQuery,string $route): string {
    return $route.'?'.http_build_query(array_merge($baseQuery,['page'=>$page]));
}

render_start('Verifikasi Skripsi','Periksa dan proses pengajuan skripsi',$u);
dashboard_nav('penjaga');
render_mid('Verifikasi Skripsi','Cari, filter, lihat detail, dan proses pengajuan',$u);
?>
<div class="card">
  <form method="get" class="toolbar">
    <input class="input search" name="q" value="<?=e($q)?>" placeholder="Cari judul, mahasiswa, NIM, program studi..." aria-label="Cari pengajuan skripsi">
    <select name="status" class="input" style="max-width:220px" aria-label="Filter status">
      <?php foreach(['all'=>'Semua Status','menunggu'=>'Menunggu','publik'=>'Disetujui','ditolak'=>'Ditolak','perlu_perbaikan'=>'Perlu Perbaikan'] as $k=>$v):?>
        <option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option>
      <?php endforeach;?>
    </select>
    <button class="btn btn-primary" type="submit">Terapkan</button>
    <a class="btn btn-light" href="<?=e('/penjaga/verifikasi/skripsi.php')?>">Reset</a>
  </form>
</div>

<div class="card" style="margin-top:16px">
  <div class="toolbar" style="margin-bottom:10px">
    <div><b><?=e($totalRows)?> pengajuan</b><div class="muted">Halaman <?=e($page)?> dari <?=e($totalPages)?></div></div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cover</th><th>Informasi Skripsi</th><th>Mahasiswa</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r):?>
      <tr>
        <td>
          <?php if(!empty($r['cover'])):?><img src="<?=e($r['cover'])?>" alt="Cover skripsi" class="table-thumb">
          <?php else:?><div class="cover-placeholder table-thumb" style="aspect-ratio:auto;font-size:9px;padding:4px">PDF</div><?php endif;?>
        </td>
        <td>
          <b><?=e($r['title'])?></b>
          <details style="margin-top:6px">
            <summary>Ringkasan detail</summary>
            <div class="text-content" style="margin-top:7px">
              Prodi: <?=e($r['study_program']??'—')?> · Tahun: <?=e($r['publication_year']??'—')?> · Pembimbing: <?=e($r['advisor']??'—')?><br>
              Kata kunci: <?=e($r['keywords']??'—')?><br>
              Abstrak: <?=e(mb_strimwidth($r['abstract']??'Belum ada abstrak.',0,220,'…'))?>
            </div>
          </details>
        </td>
        <td><?=e($r['owner_name']??$r['author']??'—')?><div class="muted">NIM <?=e($r['nim']??'—')?></div></td>
        <td><?=e($r['created_at']??'—')?></td>
        <td>
          <span class="badge <?=e(status_class($r['status']))?>"><?=e(status_label($r['status']))?></span>
          <?php if(!empty($r['rejection_reason'])):?><div class="muted" style="margin-top:6px"><?=e($r['rejection_reason'])?></div><?php endif;?>
        </td>
        <td>
          <div class="inline-form">
            <a class="btn btn-light" href="<?=e('/pengguna/detail-skripsi/index.php')?>?id=<?=$r['id']?>">Detail</a>
            <?php if(!empty($r['file_path'])):?><a class="btn btn-light" href="<?=e($r['file_path'])?>" target="_blank" rel="noopener">PDF</a><?php endif;?>
            <form method="post" class="inline-form" data-verify-form>
              <?=csrf_input()?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="reason" value="">
              <button class="btn btn-primary" name="decision" value="publik">Setujui</button>
              <button type="button" class="btn btn-danger" data-verify-decision="ditolak" data-verify-label="Tolak">Tolak</button>
              <button type="button" class="btn btn-light" data-verify-decision="perlu_perbaikan" data-verify-label="Perlu Perbaikan">Perbaikan</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach;?>
      <?php if(!$rows):?><tr><td colspan="6" class="empty">Tidak ada data skripsi untuk filter ini.</td></tr><?php endif;?>
      </tbody>
    </table>
  </div>
  <?php if($totalPages>1):?>
  <nav class="pagination" aria-label="Pagination">
    <?php if($page>1):?><a class="btn btn-light" href="<?=e(verify_thesis_page_url($page-1,$baseQuery,'/penjaga/verifikasi/skripsi.php'))?>">Sebelumnya</a><?php endif;?>
    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++):?>
      <a class="btn <?=$i===$page?'btn-primary':'btn-light'?>" href="<?=e(verify_thesis_page_url($i,$baseQuery,'/penjaga/verifikasi/skripsi.php'))?>" aria-current="<?=$i===$page?'page':'false'?>"><?=$i?></a>
    <?php endfor;?>
    <?php if($page<$totalPages):?><a class="btn btn-light" href="<?=e(verify_thesis_page_url($page+1,$baseQuery,'/penjaga/verifikasi/skripsi.php'))?>">Berikutnya</a><?php endif;?>
  </nav>
  <?php endif;?>
</div>

<div class="modal-backdrop" id="verifyReasonModal" aria-hidden="true">
  <div class="confirm-modal modal-shell" role="dialog" aria-modal="true" aria-labelledby="verifyReasonTitle">
    <div class="profile-section-title">
      <div><h2 id="verifyReasonTitle" style="margin:0">Alasan Verifikasi</h2><p class="muted" id="verifyReasonHelp">Tambahkan catatan untuk pengaju.</p></div>
      <button type="button" class="btn btn-light" id="verifyReasonClose" aria-label="Tutup">×</button>
    </div>
    <div class="form-group"><label for="verifyReasonText">Alasan / Catatan</label><textarea class="input" id="verifyReasonText" rows="5" placeholder="Tuliskan alasan atau catatan yang perlu diketahui pengaju."></textarea></div>
    <div class="form-actions"><button type="button" class="btn btn-light" id="verifyReasonCancel">Batal</button><button type="button" class="btn btn-primary" id="verifyReasonSubmit">Simpan & Proses</button></div>
  </div>
</div>

<script>
(function(){
  const modal=document.getElementById('verifyReasonModal'),text=document.getElementById('verifyReasonText'),title=document.getElementById('verifyReasonTitle'),help=document.getElementById('verifyReasonHelp');
  let currentForm=null,currentDecision='';
  function openReason(form,decision,label){
    currentForm=form;currentDecision=decision;
    title.textContent=label+' Pengajuan Skripsi';
    help.textContent=decision==='ditolak'?'Jelaskan alasan penolakan kepada pengaju.':'Jelaskan bagian yang perlu diperbaiki kepada pengaju.';
    text.value='';window.captureModalScroll?.();modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');window.lockPageScroll?.();setTimeout(()=>text.focus(),0);
  }
  function closeReason(){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');window.unlockPageScroll?.();currentForm=null;currentDecision='';window.restoreModalScroll?.();}
  document.querySelectorAll('[data-verify-form]').forEach(form=>form.addEventListener('click',e=>{const b=e.target.closest('[data-verify-decision]');if(!b)return;e.preventDefault();openReason(form,b.dataset.verifyDecision||'',b.dataset.verifyLabel||'Proses');}));
  document.getElementById('verifyReasonSubmit').addEventListener('click',function(){const reason=text.value.trim();if(!currentForm||!currentDecision)return;if(!reason){text.focus();return;}const hidden=currentForm.querySelector('input[name="reason"]');if(hidden)hidden.value=reason;const h=document.createElement('input');h.type='hidden';h.name='decision';h.value=currentDecision;currentForm.appendChild(h);currentForm.submit();});
  [document.getElementById('verifyReasonClose'),document.getElementById('verifyReasonCancel')].forEach(b=>b.addEventListener('click',closeReason));
  modal.addEventListener('click',e=>{if(e.target===modal)closeReason();});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('is-open'))closeReason();});
})();
</script>
<?php render_end(); ?>
