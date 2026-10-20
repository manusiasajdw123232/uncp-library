<?php
require __DIR__.'/../../app/core/bootstrap.php';
$u=require_admin();
require __DIR__.'/../../app/helpers/layout.php';
require __DIR__.'/../../app/helpers/settings.php';
$section=$_GET['section']??'tampilan';
if(!array_key_exists($section,settings_sections())) $section='tampilan';
render_start('Pengaturan','Konfigurasi dan preferensi administrator',$u);
dashboard_nav('admin');
render_mid('Pengaturan','Konfigurasi dan preferensi administrator',$u);
?>
<div class="settings-layout">
  <div>
    <?php render_settings_nav('/admin/settings/index.php',$section,'admin'); ?>
  </div>
  <?php render_settings_content($section,'admin','/admin/profile/index.php'); ?>
</div>
<?php render_end(); ?>
