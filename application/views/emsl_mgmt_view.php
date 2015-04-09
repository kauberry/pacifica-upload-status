<?php
  $table_object = !empty($table_object) ? $table_object : "";
  $this->load->view('pnnl_template/view_header'); 
  $js = isset($js) ? 
"<script type='text/javascript'>
//<![CDATA[
  {$js}
//]]>
</script>" : '';
  
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
                    <select id="instrument_selector" name="instrument_selector" style="width:100%;">
                      <option value>Select an Instrument...</option>
                    <?php foreach($instrument_list as $inst_id => $inst_name): ?>
                      <?php $selected_state = $inst_id == $instrument_id ? ' selected="selected"' : ''; ?>
                      <option value="<?= $inst_id ?>"<?= $selected_state ?>><?= $inst_name ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="right_block">
                    <select id="timeframe_selector" name="timeframe_selector" style="width:100%;">
                      <?php $period_list = array(
                        '1' => "Last 24 Hours",
                        '2' => "Last 48 Hours",
                        '7' => "Last 7 Days",
                        '14' => "Last 2 Weeks",
                        '30' => "Last Month",
                        '365' => "Last Year"); ?>
                      <option value>Select a Time Frame...</option>
                      <?php foreach($period_list as $period => $desc): ?>
                        <?php $selected_state = $period == $time_period ? ' selected="selected"' : ''; ?>
                      <option value="<?= $period ?>"<?= $selected_state ?>><?= $desc ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </fieldset>
            </form>
            
            
          </div>
          <div class="loading_progress_container status_messages" id="loading_status" style="display:none;">
            <span class="spinner">&nbsp;&nbsp;&nbsp;</span>
            <span id="loading_status_text">Loading...</span>
          </div>
          <div id="info_message_container">
            <h2 style="margin-top:1em;text-align:center;font-size:1.2em;"><?= $informational_message ?></h2>
          </div>
          <div class="themed" id="item_info_container" style="margin-top:20px;">
            <?=  $this->load->view('upload_item_view.html',$transaction_data); ?>
          </div>
        </div>

      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
<?= $js ?>  
</body>
</html>
