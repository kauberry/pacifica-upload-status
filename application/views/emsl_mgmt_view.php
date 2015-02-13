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
            <fieldset>
              <legend>Most Recent Upload</legend>
              <div class="full_width_block">
                <div class="left_block">
                  <?php $latest_transaction_item = array_slice($transaction_data['times'], 0,1,true); ?>
                  <?php $latest_transaction_time = array_slice(array_keys($latest_transaction_item),0,1); ?>
                  <?php $latest_transaction_time = array_pop($latest_transaction_time); ?>
                  <?php $latest_transaction_id = $latest_transaction_item[$latest_transaction_time]; ?>
                  <?php $formatted_transaction_time = new DateTime($latest_transaction_time); ?>
                  <h3>Transaction Info</h3>
                  Transaction #<span class="transaction_id"><?= $latest_transaction_id ?></span> - 
                  <time datetime="<?= $formatted_transaction_time->format('c') ?>" class="transaction_time"><?= $formatted_transaction_time->format(DATE_RFC2822); ?></span>
                </div>
                <div class="right_block">
                  <h3>Status Info</h3>
                  <ul>
                    <?php $last_status_step = array_pop(array_keys($transaction_data['transactions'][$latest_transaction_id]['status'])) ?>
                    <?php $last_status_data = $transaction_data['transactions'][$latest_transaction_id]['status'][$last_status_step]; ?>
                    <li>Last Observed Status => <?= $last_status_data['status'] ?></li>
                    <li>Message => <?= $last_status_data['message'] ?></li>
                    <li>Job ID => <?= $last_status_data['jobid'] ?></li>
                  </ul>
                </div>
              </div>
              <div class="full_width_block">
                <h3>Files List</h3>
                <ul>
                <?php foreach($transaction_data['transactions'][$latest_transaction_id]['files'] as $file_item): ?>
                  <?php $combined_path = !empty($file_item['subdir']) ? $file_item['subdir']."/" : "" ?>
                  <?php $combined_path .= $file_item['name']; ?>
                  <li id="<?= $file_item['item_id'] ?>"><?= $combined_path ?></li>
                <?php endforeach; ?>
                </ul>
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
