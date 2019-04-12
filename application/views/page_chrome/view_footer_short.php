<?php
// @codingStandardsIgnoreFile
?>
<footer class="short">
    <section id="contact_info" class="contact_info">
        <?php $git_hash_string = !empty($this->git_hash) ? " [{$this->git_hash}]" : ""; ?>
        <div id="last_update_timestamp" class="last_update_timestamp" style="display: flex; flex-direction: row-reverse">
            <span class="fa-stack fa-1x info-icon">
              <i class="fa fa-circle fa-stack-2x info-icon-background"></i>
              <i class="fa fa-circle-thin fa-stack-2x info-icon-background-ring"></i>
              <i class="fa fa-info fa-stack-1x"></i>
            </span>
            <span class="update_timestamp_text">Version <?php echo $this->application_version ?><?php echo $git_hash_string ?> / Updated <?php echo $this->last_update_time->format('n/j/Y g:i a') ?></span>
        </div>
    </section>
    <section id="pager" class="pager_section">
        <span>
            <select id="items_per_page" class="pager_block_selector" title="Select the Number of Items to Show per Page">
              <?php $items_per_page = $this->current_items_per_page; ?>
              <?php $items_per_page_defaults = ["5", "10", "20", "30", "50", "100", "250", "500", "All"]; ?>
              <?php foreach($items_per_page_defaults as $item_count): ?>
                <?php $selected = intval($item_count) == intval($items_per_page) ? " selected=\"selected\"" : ""; ?>
              <option <?= $selected; ?>value="<?= intval($item_count); ?>"><?= $item_count ?></option>
              <?php endforeach; ?>
            </select>
        </span>
        <span class="bottom_pager_block" id="bottom_pager_block"></span>
    </section>
</footer>
