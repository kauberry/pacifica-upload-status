<?php
// @codingStandardsIgnoreFile
?>
<?= doctype('html5'); ?>
<?php
  $page_header = isset($page_header) ? $page_header : "Untitled Page";
  $title = isset($title) ? $title : $page_header;
  $rss_link = isset($rss_link) ? $rss_link : "";
?>
<html>
  <head>
    <title><?= ucwords($site_identifier) ?> Status - <?= $title ?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta name="description" content="" />
    <?php //$this->load->view("{$this->template_version}_template/content_sec_policy.html"); ?>
    <meta name="keywords" content="" />
<?php $this->load->view("{$this->template_version}_template/globals"); ?>
    <script type="text/javascript">
    var base_url = "<?= base_url() ?>";
    </script>

<?= $script_uris ?>
<?= $css_uris ?>

  </head>
  <body>
    <div class="page_content">
      <header class="secondary">
          <div class="page_header">
              <div class="graphic_logo">
                  <div class="logo_container" >
                      <div class="logo_image">&nbsp;</div>
                  </div>
                  <div class="site_slogan"><?= $site_identifier ?></div>
              </div>
              <div class="text_logo">
                  <?= $site_identifier ?><span class="site_slogan">Data Management for Science</span>
              </div>
          </div>
      </header>
