<footer class="short">
    <section id="contact_info" class="contact_info">
        <?php $git_hash_string = !empty($this->git_hash) ? " [{$this->git_hash}]" : " [no hash available]"; ?>
        <div id="last_update_timestamp" style="">Version <?= $this->application_version ?><?= $git_hash_string ?> / Updated <?= $this->last_update_time->format('n/j/Y g:i a') ?></div>
    </section>
</footer>
