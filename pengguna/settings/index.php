<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_roles(['pengguna']);
require __DIR__.'/../../app/helpers/layout.php';
require __DIR__.'/../../app/helpers/settings.php';
$section=$_GET['section']??'tampilan';
if(!array_key_exists($section,settings_sections())) $section='tampilan';
render_start('Pengaturan','Preferensi akun, tampilan, keamanan, dan bantuan',$u);
dashboard_nav('pengguna');
render_mid('Pengaturan','Preferensi akun, tampilan, keamanan, dan bantuan',$u);
?>
<div class="settings-layout">
  <div>
    <?php render_settings_nav('/pengguna/settings/index.php',$section,'pengguna'); ?>
  </div>
  <?php render_settings_content($section,'pengguna','/pengguna/profile/index.php'); ?>
</div>
<?php render_end(); ?>
