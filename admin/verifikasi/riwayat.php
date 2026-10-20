<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_admin();
require __DIR__.'/../../app/helpers/layout.php';
render_start('Riwayat Verifikasi','Rekam jejak proses verifikasi buku dan skripsi',$u);
dashboard_nav('admin');
render_mid('Riwayat Verifikasi','Cari, filter, dan telusuri riwayat pemrosesan',$u);
$q=trim($_GET['q']??'');$action=$_GET['status']??'all';$type=$_GET['type']??'all';$from=trim($_GET['from']??'');$to=trim($_GET['to']??'');
$allowedA=['all','diajukan','disetujui','ditolak','perlu_perbaikan'];if(!in_array($action,$allowedA,true))$action='all';
$allowedT=['all','buku','skripsi'];if(!in_array($type,$allowedT,true))$type='all';
$where=['1=1'];$params=[];
if($q!==''){$where[]='(COALESCE(b.title,r.title) LIKE ? OR u.name LIKE ? OR p.name LIKE ?)';$like='%'.$q.'%';array_push($params,$like,$like,$like);}
if($action!=='all'){$where[]='sh.action=?';$params[]=$action;}
if($type!=='all'){$where[]='sh.item_type=?';$params[]=$type;}
if($from!==''){$where[]='sh.created_at>=?';$params[]=$from.' 00:00:00';}
if($to!==''){$where[]='sh.created_at<=?';$params[]=$to.' 23:59:59';}
$sql="SELECT sh.*,u.name user_name,p.name processor_name,COALESCE(b.title,r.title) item_title
      FROM submission_history sh
      LEFT JOIN users u ON u.id=sh.user_id
      LEFT JOIN users p ON p.id=sh.processed_by
      LEFT JOIN books b ON sh.item_type='buku' AND b.id=sh.item_id
      LEFT JOIN repositories r ON sh.item_type='skripsi' AND r.id=sh.item_id
      WHERE ".implode(' AND ',$where)." ORDER BY sh.id DESC LIMIT 500";
$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll();
?>
<div class="card">
<form method="get" class="toolbar">
  <input class="input search" name="q" value="<?=e($q)?>" placeholder="Cari judul atau pengaju..." aria-label="Cari riwayat verifikasi">
  <select class="input" name="status" style="max-width:190px">
    <?php foreach(['all'=>'Semua Status','diajukan'=>'Diajukan','disetujui'=>'Disetujui','ditolak'=>'Ditolak','perlu_perbaikan'=>'Perlu Perbaikan'] as $k=>$v):?><option value="<?=e($k)?>" <?=$action===$k?'selected':''?>><?=e($v)?></option><?php endforeach;?>
  </select>
  <select class="input" name="type" style="max-width:150px">
    <?php foreach(['all'=>'Semua Jenis','buku'=>'Buku','skripsi'=>'Skripsi'] as $k=>$v):?><option value="<?=e($k)?>" <?=$type===$k?'selected':''?>><?=e($v)?></option><?php endforeach;?>
  </select>
  <input class="input" type="date" name="from" value="<?=e($from)?>" style="max-width:170px" aria-label="Tanggal mulai">
  <input class="input" type="date" name="to" value="<?=e($to)?>" style="max-width:170px" aria-label="Tanggal akhir">
  <button class="btn btn-primary">Filter</button>
</form>
</div>
<div class="card" style="margin-top:16px">
<div class="table-wrap"><table class="table"><thead><tr><th>No</th><th>Jenis</th><th>Judul</th><th>Pengaju</th><th>Tanggal</th><th>Diproses Oleh</th><th>Status</th><th>Alasan</th></tr></thead>
<tbody>
<?php foreach($rows as $i=>$r):?>
<tr>
<td><?=e($i+1)?></td>
<td><?=e(ucfirst($r['item_type']))?></td>
<td><b><?=e($r['item_title']??('ID #'.$r['item_id']))?></b><div class="muted">ID #<?=e($r['item_id'])?></div></td>
<td><?=e($r['user_name']??'—')?></td>
<td><?=e($r['created_at'])?></td>
<td><?=e($r['processor_name']??'—')?></td>
<td><span class="badge <?=e(status_class($r['action']))?>"><?=e(status_label($r['action']))?></span></td>
<td><?=e($r['note']??'—')?></td>
</tr>
<?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="8" class="empty">Belum ada riwayat yang sesuai filter.</td></tr><?php endif;?>
</tbody></table></div>
</div>
<?php render_end(); ?>
