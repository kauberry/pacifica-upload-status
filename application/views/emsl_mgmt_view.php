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
              <div class="full_width_block" style="margin-left:40px;">
                <h2>Files List => Transaction #<?= $transaction_id ?></h2>
                <div class="disclosure_block">
                  <?php $ul_list = ""; ?>
                  <?php //format_folder_structure($transaction_info['files'], $ul_list); ?>
                  <?= $ul_list ?>
                </div>
              </div>
              
              
            </fieldset>
            <br />
            <?php endforeach; ?>
          </div>
          <!-- <div class="themed">
            <fieldset>
              <legend>Most Recent Upload</legend>
              <?php $latest_transaction_item = array_slice($transaction_data['times'], 0,1,true); ?>
              <?php $latest_transaction_time = array_slice(array_keys($latest_transaction_item),0,1); ?>
              <?php $latest_transaction_time = array_pop($latest_transaction_time); ?>
              <?php $latest_transaction_id = $latest_transaction_item[$latest_transaction_time]; ?>
              <?php $latest_transaction_info = $transaction_data['transactions'][$latest_transaction_id]; ?>
              <?php $latest_transaction_steps = $latest_transaction_info['status']; ?>
              <?php krsort($latest_transaction_steps); ?>
              <?php $latest_step_info = array_pop(array_slice($latest_transaction_steps, 0,1, true)); ?>
              <?php $latest_step = $latest_step_info['step']; ?>
              <?php $formatted_transaction_time = new DateTime($latest_transaction_time); ?>
                <div class="bar_holder">
                  <?php for($i=0;$i<=6;$i++): ?>
                    <?php $classname = $i <= $latest_step ? 'green_bar_end' : 'orange_bar_end'; ?>
                    <?php $relname = $i <= $latest_step ? 'Completed' : 'Unknown'; ?>
                  <span class="<?= $classname ?> block_<?= $i ?>" rel="<?= $relname ?>"><?= $status_list[$i] ?></span>
                  <?php endfor; ?>
                </div>
                <br />
              <div class="full_width_block" style="margin-left:40px;">
                <h2>Upload Info</h2>
                <?php $friendly_upload_time = $formatted_transaction_time->format('g:ia \o\n D, M j Y'); ?>
                <div style="font-size:1.2em;">Completed <time datetime="<?= $formatted_transaction_time->format('c') ?>" class="transaction_time"><?= $friendly_upload_time ?></time></div>
              </div>
              <br />
              <div class="full_width_block" style="margin-left:40px;">
                <h2>Files List</h2>
                <ul>
                <?php foreach($transaction_data['transactions'][$latest_transaction_id]['files'] as $file_item): ?>
                  <?php $combined_path = !empty($file_item['subdir']) ? $file_item['subdir']."/" : "" ?>
                  <?php $combined_path .= $file_item['name']; ?>
                  <li id="<?= $file_item['item_id'] ?>"><?= $combined_path ?></li>
                <?php endforeach; ?>
                </ul>
              </div>
            </fieldset>
          </div> -->          
        </div>

      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
