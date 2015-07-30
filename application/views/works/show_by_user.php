<h2><?php echo $headline ?></h2>
<br />

<h3><?php echo __('Pending works') ?></h3>
<?php echo $pending_works_grid ?>
<br />
<?php echo __('Sum') ?>:
<?php echo $total_pending['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $total_pending['kms'] ?> km,
<?php echo number_format($total_pending['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />

<h3><?php echo __('Approved works') ?></h3>
<?php echo $approved_works_grid ?>
<br />
<?php echo __('Sum') ?>:
<?php echo $total_approved['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $total_approved['kms'] ?> km,
<?php echo number_format($total_approved['rating'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />

<h3><?php echo __('Rejected works') ?></h3>
<?php echo $rejected_works_grid ?>
<br/>
<?php echo __('Sum') ?>:
<?php echo $total_rejected['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $total_rejected['kms'] ?> km,
<?php echo number_format($total_rejected['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br/>