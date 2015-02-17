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
                        '7' => "Last 7 Days",
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
          <br />
          <div class="themed">
            <?php foreach($transaction_data['times'] as $upload_time_string => $transaction_id): ?>
              <?php 
                $upload_time = new DateTime($upload_time_string);
                $transaction_info = $transaction_data['transactions'][$transaction_id];
                $friendly_upload_time = $upload_time->format('g:ia \o\n D, M j Y');
                $status_steps = $transaction_info['status'];
                krsort($status_steps);
                $latest_step_info =  array_pop(array_slice($status_steps, 0,1, true));
                $latest_step = $latest_step_info['step'];
              ?>
            <fieldset>
              <legend>Uploaded at <?= $friendly_upload_time ?></legend>
              <div class="bar_holder">
                <?php for($i=0;$i<=6;$i++): ?>
                  <?php $classname = $i <= $latest_step ? 'green_bar_end' : 'orange_bar_end'; ?>
                  <?php $relname = $i <= $latest_step ? 'Completed' : 'Unknown'; ?>
                <span class="<?= $classname ?> block_<?= $i ?>" rel="<?= $relname ?>"><?= $status_list[$i] ?></span>
                <?php endfor; ?>
              </div>
              <br />
              <div class="full_width_block" style="margin-left:40px;width:95%;">
                <h2>Files List => Transaction #<?= $transaction_id ?></h2>
                <div class="disclosure_block">
                  <div id="tree_<?= $transaction_id ?>" class="tree_holder">
                    <ul id="treeData_<?= $transaction_id ?>" style="display:none;">
                    <?php $ul_list = ""; ?>
                    <?php format_folder_structure($transaction_info['files'], $ul_list, "Root Directory"); ?>
                    <?= $ul_list ?>
                    </ul>
                  </div>
                </div>
              </div>
              
              
            </fieldset>
            <br />
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
