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
        <div style="position:relative;">
          <div class="loading_progress_container status_messages" id="loading_status" style="display:none;">
            <span class="spinner">&nbsp;&nbsp;&nbsp;</span>
            <span id="loading_status_text">Loading...</span>
          </div>
          <div class="themed" id="item_info_container" style="margin-top:20px;">
            <?=  $this->load->view('upload_item_view.html',$transaction_data); ?>
          </div>
        </div>

      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
<script type="application/javascript">
$(function(){      
  $('.tree_holder').each(function(index, el){
    // $(el).fancytree({
      // lazyLoad: get_tree_data
    // });
  });
  window.setInterval(update_breadcrumbs,5000);
});
  
</script>
</html>
