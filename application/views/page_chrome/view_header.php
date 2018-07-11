<?php
// @codingStandardsIgnoreFile
?>
<?= doctype('html5'); ?>
<?php
  $page_header = isset($page_header) ? $page_header : "Untitled Page";
  $title = isset($title) ? $title : $page_header;
?>
<html>
  <head>
    <title><?= ucwords($site_identifier) ?> &ndash; <?= $title ?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
<?php $this->load->view("page_chrome/globals"); ?>
    <script type="text/javascript">
    var base_url = "<?= base_url() ?>";
    </script>

<?= $script_uris ?>
<?= $css_uris ?>

  </head>
  <body>
    <div class="page_hider" id="page_hider_working">
        <div class="loading_status">
            <div class="spinner_message_container">
                <div id="doi_loading_status_text">Contacting DRHub Servers...</div>
                <div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
            </div>
        </div>
        <div class="page_hider_coverslip">&nbsp;</div>
    </div>
    <div class="page_content">
      <header class="secondary">
          <div class="page_header">
            <div class="logo_container">
              <div class="logo_image">&nbsp;</div>
              <span class="site_name"><?= $site_identifier ?></span>
              <span class="site_slogan"><?= $site_slogan ?></span>
            </div>
          </div>
      </header>
