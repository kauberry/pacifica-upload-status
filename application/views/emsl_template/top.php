<?php
// @codingStandardsIgnoreFile
?>
<div id="subBanner" style="position:relative;">
  <?php if(empty($banner_file)) : ?>
  <div class="banner_bar_background">
    <div class="banner_bar banner_bar_left banner_bar_<?= $this->site_color ?>">
      <div class='user_login_info'>Signed in as: <?= $logged_in_user ?></div>
    </div>
    <div class="banner_bar banner_bar_right banner_bar_grey">
      <div id="site_label"><?= ucwords($site_identifier) ?> Status Reporting</div>
      <div id="last_update_timestamp" style="">Last Source Update: <?= $this->last_update_time->format('n/j/Y g:i a') ?></div>
    </div>
  </div>
    <?php else: ?>
    <div class='user_login_info'>Signed in as: <?= $logged_in_user ?></div>
    <img src="<?=$banner_path ?>" <?=$banner_dimensions?> alt="" />
    <?php endif; ?>
</div>
