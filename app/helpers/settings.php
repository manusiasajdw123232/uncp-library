<?php
function settings_sections(): array {
    return [
        'tampilan'=>['Tampilan','Atur tema, ukuran teks, kepadatan, animasi, dan sidebar.'],
        'akun'=>['Akun','Kelola informasi akun dan data profil.'],
        'keamanan'=>['Keamanan','Kelola password dan keamanan sesi.'],
        'notifikasi'=>['Notifikasi','Atur pemberitahuan pengajuan dan sistem.'],
        'privasi'=>['Privasi','Informasi tentang data dan hak akses.'],
        'bantuan'=>['Bantuan','FAQ, panduan singkat, dan kontak bantuan.'],
        'tentang'=>['Tentang Aplikasi','Informasi Perpustakaan UNCP.'],
    ];
}
function render_settings_nav(string $basePath,string $active,string $role): void {
    echo '<nav class="card settings-nav" aria-label="Pengaturan">';
    $icons=['tampilan'=>'◐','akun'=>'♙','keamanan'=>'▣','notifikasi'=>'●','privasi'=>'◌','bantuan'=>'?','tentang'=>'ⓘ'];
    foreach(settings_sections() as $key=>$meta){
        $cls=$key===$active?' active':'';
        echo '<a class="'.$cls.'" href="'.$basePath.'?section='.$key.'"><span aria-hidden="true">'.e($icons[$key]??'•').'</span><span>'.e($meta[0]).'</span></a>';
    }
    echo '</nav>';
}
function render_theme_cards(): void { ?>
<div class="theme-grid" id="themeGrid">
  <?php foreach([
    ['light','Terang','Gunakan tampilan terang untuk penggunaan pada kondisi cahaya tinggi.'],
    ['dark','Gelap','Gunakan tampilan gelap yang nyaman untuk penggunaan malam hari.'],
    ['system','Sistem','Sesuaikan dengan pengaturan tema perangkat.'],
  ] as $card): ?>
  <label class="theme-card" data-theme-option="<?=e($card[0])?>">
    <input type="radio" name="theme-option" value="<?=e($card[0])?>">
    <span class="check">✓</span><div class="theme-preview <?=e($card[0])?>"></div>
    <b><?=e($card[1])?></b><small><?=e($card[2])?></small>
  </label>
  <?php endforeach; ?>
</div>
<script>
(function(){
  const cards=document.querySelectorAll('[data-theme-option]');
  const select=v=>cards.forEach(c=>c.classList.toggle('selected',c.dataset.themeOption===v));
  select(localStorage.getItem('uncp-theme')||'light');
  cards.forEach(card=>card.addEventListener('click',()=>{window.setTheme(card.dataset.themeOption);select(card.dataset.themeOption);}));
})();
</script>
<?php }
function render_settings_content(string $section,string $role,string $profileUrl): void {
    $sections=settings_sections();
    $meta=$sections[$section]??$sections['tampilan'];
    $currentUser=auth_user()??[];
    ?>
    <section class="card settings-panel">
      <div class="profile-section-title"><div><h2 style="margin:0"><?=e($meta[0])?></h2><p class="muted" style="margin:5px 0 0"><?=e($meta[1])?></p></div></div>
      <?php if($section==='tampilan'): ?>
        <div class="setting-block"><h3>Tema</h3><?php render_theme_cards(); ?></div>
        <div class="setting-list" style="margin-top:22px">
          <div class="setting-row"><div class="setting-copy"><b>Ukuran Teks</b><small>Kecil, sedang, atau besar.</small></div><select id="textSize" class="input"><option value="small">Kecil</option><option value="medium">Sedang</option><option value="large">Besar</option></select></div>
          <div class="setting-row"><div class="setting-copy"><b>Kepadatan Tampilan</b><small>Atur jarak agar ringkas, normal, atau nyaman.</small></div><select id="density" class="input"><option value="compact">Ringkas</option><option value="normal">Normal</option><option value="comfortable">Nyaman</option></select></div>
          <div class="setting-row"><div class="setting-copy"><b>Animasi</b><small>Pilih tingkat animasi antarmuka.</small></div><select id="motion" class="input"><option value="full">Penuh</option><option value="light">Ringan</option><option value="minimal">Minimal</option><option value="off">Nonaktif</option></select></div>
          <div class="setting-row"><div class="setting-copy"><b>Sidebar</b><small>Sidebar normal atau ringkas pada layar desktop/tablet.</small></div><select id="sidebarMode" class="input"><option value="default">Normal</option><option value="compact">Ringkas</option></select></div>
        </div>
        <script>
        (function(){
          const values={textSize:['small','medium','large'],density:['compact','normal','comfortable'],motion:['full','light','minimal','off'],sidebarMode:['default','compact']};
          const keys={textSize:'text-size',density:'density',motion:'motion',sidebarMode:'sidebar'};
          Object.keys(values).forEach(function(id){const el=document.getElementById(id);if(!el)return;el.value=localStorage.getItem('uncp-'+keys[id])||({textSize:'medium',density:'normal',motion:'light',sidebarMode:'default'}[id]);el.addEventListener('change',function(){window.setDisplaySetting(keys[id],el.value);if(id==='sidebarMode')location.reload();});});
        })();
        </script>
      <?php elseif($section==='akun'): ?>
        <div class="detail-list">
          <div class="detail-item"><small>Nama</small><b><?=e($currentUser['name']??'—')?></b></div>
          <div class="detail-item"><small>Email</small><b><?=e($currentUser['email']??'—')?></b></div>
          <div class="detail-item"><small>Username / NIM</small><b><?=e($currentUser['nim']??$currentUser['username']??'—')?></b></div>
          <div class="detail-item"><small>Nomor HP</small><b><?=e($currentUser['phone']??'—')?></b></div>
          <?php if($role==='pengguna'): ?><div class="detail-item"><small>Program Studi</small><b><?=e($currentUser['study_program']??'—')?></b></div><?php endif; ?>
          <div class="detail-item"><small>Role</small><b><?=e(ucfirst($role))?></b></div>
        </div>
        <div class="mobile-stack" style="margin-top:18px"><a class="btn btn-primary" href="<?=e($profileUrl)?>#edit-profile">Edit Profil</a></div>
      <?php elseif($section==='keamanan'): ?>
        <div class="setting-list">
          <div class="setting-row"><div class="setting-copy"><b>Ubah Password</b><small>Konfirmasi password lama dan buat password baru.</small></div><a class="btn btn-dark" href="<?=e($profileUrl)?>#ubah-password">Ubah Password</a></div>
          <div class="setting-row"><div class="setting-copy"><b>Sesi Login</b><small>Logout tersedia di paling bawah sidebar.</small></div><span class="badge green">Aktif</span></div>
          <div class="setting-row"><div class="setting-copy"><b>Login Terakhir</b><small><?=e($currentUser['last_login_at']??'Belum tersedia')?></small></div></div>
        </div>
      <?php elseif($section==='notifikasi'): ?>
        <div class="setting-list">
          <?php foreach([
            ['book','Notifikasi Pengajuan Buku','Beri tahu saat pengajuan buku berubah status.'],
            ['thesis','Notifikasi Pengajuan Skripsi','Beri tahu saat pengajuan skripsi berubah status.'],
            ['verification','Notifikasi Verifikasi','Beri tahu saat hasil verifikasi tersedia.'],
            ['system','Notifikasi Sistem','Tampilkan pesan sistem penting dari aplikasi.'],
          ] as $n): ?>
          <div class="setting-row"><div class="setting-copy"><b><?=e($n[1])?></b><small><?=e($n[2])?></small></div><label class="switch"><input type="checkbox" data-notify="<?=e($n[0])?>"><span></span></label></div>
          <?php endforeach; ?>
        </div>
        <script>(function(){document.querySelectorAll('[data-notify]').forEach(function(el){const k='uncp-notify-'+el.dataset.notify;el.checked=(localStorage.getItem(k)??'1')==='1';el.addEventListener('change',()=>localStorage.setItem(k,el.checked?'1':'0'));});})();</script>
      <?php elseif($section==='privasi'): ?>
        <div class="text-content"><p>Data akun dan profil digunakan untuk autentikasi dan pengelolaan perpustakaan.</p><p>Data pengajuan buku dan skripsi dapat diproses oleh Admin/Penjaga sesuai role dan permission backend.</p><p>Akses dokumen mengikuti status publikasi dan izin sistem.</p></div>
      <?php elseif($section==='bantuan'): ?>
        <div class="faq">
          <details open><summary>Bagaimana cara membuat akun?</summary><p class="muted">Gunakan Daftar Akun Pengguna dan lengkapi identitas yang diminta.</p></details>
          <details><summary>Bagaimana cara login?</summary><p class="muted">Masukkan username, NIM, atau email dan password. Role dibaca otomatis dari database.</p></details>
          <details><summary>Bagaimana cara membaca buku atau skripsi?</summary><p class="muted">Buka katalog, pilih item, lalu gunakan tombol Baca jika file PDF tersedia.</p></details>
          <details><summary>Bagaimana cara menambahkan buku atau skripsi?</summary><p class="muted">Gunakan menu Tambah Buku/Skripsi. Data pengguna berstatus Menunggu Verifikasi hingga diproses.</p></details>
          <details><summary>Bagaimana cara mengubah password?</summary><p class="muted">Buka Pengaturan → Keamanan atau Profil → Ubah Password.</p></details>
          <details><summary>Bagaimana proses verifikasi?</summary><p class="muted">Admin/Penjaga dapat menyetujui, menolak dengan alasan, atau meminta perbaikan.</p></details>
        </div>
      <?php elseif($section==='tentang'): ?>
        <div class="about-card text-content"><div class="auth-brand" style="margin:0 0 15px;align-items:flex-start;text-align:left"><img src="/assets/images/logo-uncp.svg" alt="Logo UNCP" class="brand-logo"><div><h3 style="margin:0;color:var(--ink)">Perpustakaan Universitas Cokroaminoto Palopo</h3><small>Portal katalog buku dan repository akademik</small></div></div><p>Versi aplikasi: 2.0 — antarmuka responsive dan adaptive untuk smartphone, tablet, laptop, dan desktop.</p><p>Fokus: katalog koleksi, repository akademik, pengajuan karya, verifikasi, notifikasi, profil, dan pengaturan akun.</p></div>
      <?php endif; ?>
    </section>
    <?php
}
