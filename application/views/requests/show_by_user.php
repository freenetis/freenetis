<h2><?php echo $headline ?></h2>
<br />

<h3><?php echo __('Pending requests') ?></h3>
<?php echo $pending_requests_grid ?>
<br />
<?php echo __('Sum') ?>:
<?php echo number_format($total_pending['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />

<h3><?php echo __('Approved requests') ?></h3>
<?php echo $approved_requests_grid ?>
<br />
<?php echo __('Sum') ?>:
<?php echo number_format($total_approved['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />

<h3><?php echo __('Rejected requests') ?></h3>
<?php echo $rejected_requests_grid ?>
<br/>
<?php echo __('Sum') ?>:
<?php echo number_format($total_rejected['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br/>