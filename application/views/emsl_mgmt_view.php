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
          <div class="form_container">
            <form id="instrument_selection" class="themed">
              <fieldset id="inst_select_container">
                <legend>Instrument Selection</legend>
                <div class="full_width_block">
                  <div class="left_block">
                    <select>
                      <option value>Select an Instrument...</option>
                    <?php foreach($instrument_list as $inst_id => $inst_name): ?>
                      <option value="<?= $inst_id ?>"><?= $inst_name ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="right_block">
                    <select style="width:100%;">
                      <option value>Select a Time Frame...</option>
                      <option value="-1 day">Last 24 Hours</option>
                      <option value="-7 days">Last 7 Days</option>
                      <option value="-1 month">Last Month</option>
                      <option value="-1 year">Last Year</option>
                    </select>
                  </div>
                </div>
              </fieldset>
            </form>
          </div>
          <div class="themed">
            <fieldset>
              <legend>Most Recent Upload</legend>
              <div class="full_width_block">
                
              </div>
            </fieldset>
          </div>
          
        </div>

      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
