<?php
  $table_object = !empty($table_object) ? $table_object : "";
  $this->load->view('pnnl_template/view_header'); 
?>
<body class="col1">
  <?php $this->load->view('pnnl_template/intranet_banner'); ?>
  <div id="page">
    <?php $this->load->view('pnnl_template/top',$navData['current_page_info']); ?>
    <div id="container">
      <div id="main">
        
        <h1 class="underline"><?= $page_header ?></h1>

      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
