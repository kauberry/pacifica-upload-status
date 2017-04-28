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
    <title>MyEMSL Status - <?= $title ?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta name="description" content="" />
    <?php //$this->load->view("{$this->template_version}_template/content_sec_policy.html"); ?>
    <meta name="keywords" content="" />
<?php $this->load->view("{$this->template_version}_template/globals"); ?>

<?= $script_uris ?>
<?= $css_uris ?>

    <script type="text/javascript">
      var base_url = "<?= base_url() ?>";
    </script>
  </head>
  <body>
    <div class="page_content">
      <header class="secondary">
          <div class="page_header">
              <div class="graphic_logo">
                  <div class="logo_container" >
                      <div class="logo_image">&nbsp;</div>
                  </div>
                  <div class="site_slogan">Environmental Molecular Sciences Laboratory</div>
              </div>
              <div class="text_logo">
                  Pacifica<span class="site_slogan">Data Management for Science</span>
              </div>
              <div id="menu_block_container" style="display:none;">
                  <nav>
                      <ul id="page_menu">
                          <li><a href="https://www.emsl.pnl.gov/emslweb/">Home</a><span class='menu_separator'>|</span></li>
                          <li>About<span class='menu_separator'>|</span></li>
                          <li>Science<span class='menu_separator'>|</span></li>
                          <li>Capabilities<span class='menu_separator'>|</span></li>
                          <li>Working With Us<span class='menu_separator'>|</span></li>
                          <li>News</li>
                      </ul>
                  </nav>
              </div>
          </div>
      </header>
