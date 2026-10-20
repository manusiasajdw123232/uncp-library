<?php require __DIR__.'/../../app/core/bootstrap.php';$u=require_admin();require __DIR__.'/../../app/helpers/layout.php';render_start('Dashboard Admin','Monitoring dan pengelolaan perpustakaan',$u);dashboard_nav('admin');render_mid('Dashboard Admin','Ringkasan sistem Perpustakaan UNCP',$u);$stats=['users'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='pengguna') AND status='aktif'")->fetchColumn(),'books'=>(int)$pdo->query("SELECT COUNT(*) FROM books WHERE approval_status='disetujui'")->fetchColumn(),'thesis'=>(int)$pdo->query("SELECT COUNT(*) FROM repositories WHERE type='skripsi' AND status='publik'")->fetchColumn(),'pending'=>(int)$pdo->query("SELECT (SELECT COUNT(*) FROM books WHERE approval_status<>'disetujui')+(SELECT COUNT(*) FROM repositories WHERE type='skripsi' AND status<>'publik')")->fetchColumn()];?>
<div class="hero"><h2>Semua data utama dalam satu layar.</h2><p>Kelola pengguna, katalog, repository, verifikasi, pengaturan, dan backup dari panel admin.</p></div><div class="grid grid-4" style="margin-top:20px"><div class="card stat"><div class="label">Pengguna Aktif</div><div class="num" id="adminUsers"><?=$stats['users']?></div></div><div class="card stat"><div class="label">Buku Disetujui</div><div class="num" id="adminBooks"><?=$stats['books']?></div></div><div class="card stat"><div class="label">Skripsi Publik</div><div class="num" id="adminThesis"><?=$stats['thesis']?></div></div><div class="card stat"><div class="label">Menunggu Verifikasi</div><div class="num" id="adminPending"><?=$stats['pending']?></div></div></div><div class="grid grid-2" style="margin-top:20px"><div class="card"><h3>Status Verifikasi</h3><div class="chart-wrap"><canvas id="verificationChart"></canvas></div></div><div class="card"><h3>Aktivitas Terbaru</h3><?php $a=$pdo->query('SELECT action,description,created_at FROM activity_logs ORDER BY id DESC LIMIT 8')->fetchAll();foreach($a as $r):?><div style="padding:10px 0;border-bottom:1px solid var(--line)"><b><?=e($r['action'])?></b><div class="muted" style="font-size:13px"><?=e($r['description']??'')?></div><small class="muted"><?=e($r['created_at'])?></small></div><?php endforeach;?></div></div><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>
(function(){
  const canvas=document.getElementById('verificationChart');
  if(!canvas)return;

  const wrapper=canvas.closest('.chart-wrap');
  let chart=null;
  let lastData=null;
  let refreshTimer=null;
  let requestInFlight=false;

  const fallback=document.createElement('div');
  fallback.className='empty';
  fallback.style.marginTop='12px';
  fallback.textContent='Grafik tidak tersedia saat Chart.js belum termuat.';
  fallback.hidden=true;
  wrapper?.parentNode?.appendChild(fallback);

  const api='/api/dashboard/statistics/index.php';
  const initial={
    total_books:<?= $stats['books']?>,
    active_users:<?= $stats['users']?>,
    public_repository:<?= $stats['thesis']?>,
    pending_submissions:<?= $stats['pending']?>
  };

  function themeColors(){
    const cs=getComputedStyle(document.documentElement);
    return {
      text:cs.getPropertyValue('--ink').trim()||'#171a20',
      line:cs.getPropertyValue('--line').trim()||'#e5e8ed'
    };
  }

  function values(){
    const d=lastData||initial;
    return [
      Number(d.total_books)||0,
      Number(d.public_repository)||0,
      Number(d.pending_submissions)||0
    ];
  }

  function draw(){
    const colors=themeColors();
    const data=values();

    if(!window.Chart){
      canvas.hidden=true;
      if(wrapper)wrapper.hidden=true;
      fallback.hidden=false;
      return;
    }

    if(wrapper)wrapper.hidden=false;
    canvas.hidden=false;
    fallback.hidden=true;

    Chart.defaults.color=colors.text;
    Chart.defaults.borderColor=colors.line;

    if(!chart){
      chart=new Chart(canvas,{
        type:'doughnut',
        data:{
          labels:['Buku disetujui','Skripsi publik','Menunggu'],
          datasets:[{
            data,
            backgroundColor:['#2f8f5b','#6b7280','#d49a12'],
            borderColor:colors.line,
            borderWidth:1
          }]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          animation:false,
          resizeDelay:120,
          plugins:{
            legend:{
              position:'bottom',
              labels:{usePointStyle:true,padding:18,color:colors.text}
            }
          }
        }
      });
      return;
    }

    chart.data.datasets[0].data=data;
    chart.data.datasets[0].borderColor=colors.line;
    chart.options.plugins.legend.labels.color=colors.text;
    chart.update('none');
  }

  async function refresh(){
    if(requestInFlight || document.hidden)return;
    requestInFlight=true;
    try{
      const r=await fetch(api,{headers:{Accept:'application/json'},cache:'no-store'});
      if(!r.ok)throw new Error('HTTP '+r.status);
      const d=await r.json();
      lastData=d;
      document.getElementById('adminUsers').textContent=Number(d.active_users)||0;
      document.getElementById('adminBooks').textContent=Number(d.total_books)||0;
      document.getElementById('adminThesis').textContent=Number(d.public_repository)||0;
      document.getElementById('adminPending').textContent=Number(d.pending_submissions)||0;
      draw();
    }catch(e){
      /* Pertahankan data terakhir yang sudah terlihat. */
    }finally{
      requestInFlight=false;
    }
  }

  function schedulePolling(){
    if(refreshTimer)window.clearInterval(refreshTimer);
    if(!document.hidden)refreshTimer=window.setInterval(refresh,15000);
  }

  document.addEventListener('visibilitychange',function(){
    schedulePolling();
    if(!document.hidden)refresh();
  });

  window.addEventListener('uncp:themechange',draw);
  window.addEventListener('resize',()=>{if(chart)chart.resize();});

  draw();
  refresh();
  schedulePolling();
})();
</script><?php render_end();?>
