<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_roles(['penjaga']);
require __DIR__.'/../../app/helpers/layout.php';
require __DIR__.'/../../app/helpers/settings.php';
$section=$_GET['section']??'tampilan';
if(!array_key_exists($section,settings_sections())) $section='tampilan';
render_start('Pengaturan','Preferensi akun, tampilan, keamanan, dan bantuan',$u);
dashboard_nav('penjaga');
render_mid('Pengaturan','Preferensi akun, tampilan, keamanan, dan bantuan',$u);
?>
<div class="settings-layout">
  <div>
    <?php render_settings_nav('/penjaga/settings/index.php',$section,'penjaga'); ?>
  </div>
  <?php render_settings_content($section,'penjaga','/penjaga/profile/index.php'); ?>
</div>
<?php render_end(); ?>
