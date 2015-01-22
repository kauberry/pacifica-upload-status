<?php
  $table_object = !empty($table_object) ? $table_object : "";
  $this->load->view('pnnl_template/view_header'); 
?>
<body class="col2">
  <?php $this->load->view('pnnl_template/intranet_banner'); ?>
  <div id="page">
    <?php $this->load->view('pnnl_template/top',$navData['current_page_info']); ?>
    <div id="container">
      <div id="main">
        
        <h1 class="underline"><?= $page_header ?></h1>
<?php foreach($equipment as $location => $items): ?>        
        <h2 class="location_header"><?= $location ?></h2>
        <div class="location_entry">
          <div>
            <ul class="type_entry">
  <?php foreach($items as $item_type => $item_entries): ?>
              <li class="type_header"><h3><?= ucwords($item_type) ?></h3></li>
              <ul class="equipment_entry">
    <?php foreach($item_entries as $index => $item): ?>
                <li>
                  <?= anchor("equipment/{$item_type}/{$index}",$item['name']); ?>
                  <?= $item['display'] ?>
                </li>
    <?php endforeach; ?>
              </ul>
  <?php endforeach; ?>
            </ul>
          </div>
        </ul>
      </div>
<?php endforeach; ?>
      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
