<h2><?php echo $headline ?></h2>
<br />

<?php if ($show_concepts): ?>
<h3><?php echo __('Your concepts of work reports') ?></h3>
<?php echo $grid_concepts ?>
<br />
<?php echo __('Sum') ?>:
<?php echo $stats_concepts['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $stats_concepts['kms'] ?> km,
<?php echo number_format($stats_concepts['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />
<?php endif; ?>

<h3><?php echo __('Pending work reports') ?></h3>
<?php echo $grid_pending ?>
<br />
<?php echo __('Sum') ?>:
<?php echo $stats_pending['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $stats_pending['kms'] ?> km,
<?php echo number_format($stats_pending['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />

<h3><?php echo __('Approved work reports') ?></h3>
<?php echo $grid_approved ?>
<br />
<?php echo __('Sum') ?>:
<?php echo $stats_approved['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $stats_approved['kms'] ?> km,
<?php echo number_format($stats_approved['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />

<h3><?php echo __('Rejected work reports') ?></h3>
<?php echo $grid_rejected ?>
<br />
<?php echo __('Sum') ?>:
<?php echo $stats_rejected['hours'] ?> <?php echo __('Hours') ?>,
<?php echo $stats_rejected['kms'] ?> km,
<?php echo number_format($stats_rejected['suggest_amount'], 2, ',', ' ') ?> <?php echo __($this->settings->get('currency')) ?>
<br /><br /><br />