<?php
  $table_object = !empty($table_object) ? $table_object : "";
  $this->template_version = $this->config->item('template');
  $this->load->view("{$this->template_version}_template/view_header");
  $js = isset($js) ? $js : "";

?>

      <div id="container">
        <div id="main">
          <div id="header_container">
            <h1 class="underline"><?= $page_header ?></h1>
            <div id="login_id_container">
              <em><?= $this->nav_info['current_page_info']['logged_in_user'] ?></em>
            </div>
          </div>
          <div class="form_container">

            <form id="instrument_selection" class="themed">
              <fieldset id="inst_select_container">
                <legend>Instrument Selection</legend>
                <div class="full_width_block" id="proposal_selector_container">
                  <select id="proposal_selector" class="criterion_selector" name="proposal_selector" style="width:96%;">
                    <?php $this->load->view('proposal_selector_insert.html', array('proposal_list' => $proposal_list, 'selected_proposal' => $selected_proposal)); ?>
                  </select>
                </div>

                <div class="full_width_block" style="margin-top:1em;">
                  <div class="left_block" id="instrument_selector_container">
                    <select id="instrument_selector" class="criterion_selector" disabled="disabled" name="instrument_selector" style="width:95%;">
                        <option></option>
                    </select>
                    <div class="selector_spinner_container" id="instrument_selector_spinner"></div>
                  </div>
                  <div class="right_block" id="timeframe_selector_container">
                    <select id="timeframe_selector" class="criterion_selector" name="timeframe_selector" style="width:100%;">
                        <?php $period_list = array(
                        '1' => "Last 24 Hours",
                        '2' => "Last 48 Hours",
                        '7' => "Last 7 Days",
                        '14' => "Last 2 Weeks",
                        '30' => "Last Month",
                        '365' => "Last Year"); ?>
                      <option></option>
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
            <?php $hide_cart_data = empty($cart_data['carts']) ? "display:none;" : ""; ?>
          <div id="cart_listing_container" class="themed" style="<?= $hide_cart_data ?>margin-top:1em;">
            <fieldset id="cart_listing_fieldset">
              <legend>Download Queue</legend>
              <div id="cart_listing">
                <?php $this->load->view('cart_list_insert.html', $cart_data); ?>
              </div>
            </fieldset>
          </div>

          <div class="loading_status loading_progress_container status_messages" id="loading_status" style="display:none;">
            <span class="spinner">&nbsp;&nbsp;&nbsp;</span>
            <span id="loading_status_text">Loading...</span>
          </div>
          <div class="themed" id="item_info_container" style="margin-top:20px;"></div>
        </div>
      </div>
    <?php $this->load->view("{$this->template_version}_template/view_footer_short"); ?>
  </div>
<script type='text/javascript'>
//<![CDATA[
    <?= $js ?>
//]]>
</script>

</body>
</html>
