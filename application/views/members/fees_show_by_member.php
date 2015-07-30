<h2><?php echo __('List of tariffs of member') . ' ' . $member->name ?></h2>
<br />
<?php echo implode(' | ', $links) ?>
<br />

<?php foreach ($fee_types as $fee_type_id => $fee_type): ?>
	<br /><br />
	<h3><?php echo __('Type') ?> <?php echo $fee_type ?></h3>
	<?php echo $members_fees_grids[$fee_type_id] ?>
<?php endforeach ?>
	
<?php if (!count($fee_types)): ?>
	<br />
	<br />
	<b><?php echo __('This member has default system member fee %d %s.', array(0 => $default_fee, 1 => __($this->settings->get('currency')))) ?></b>
	<?php
 endif ?>