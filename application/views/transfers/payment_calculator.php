<h2><?php echo __('Payment calculator for member') ?> <?php echo $account->member->name ?></h2><br />

<table class="extended">
	<tr>
		<th><?php echo __('Current credit') ?></th>
		<td><?php echo $account->balance ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Regular member fee') ?></th>
		<td><?php echo $member_fee ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<?php if ($entrance_fee_left): ?>
	<tr>
		<th colspan="2"><?php echo __('Entrance fee repayments') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Entrance date') ?></th>
		<td><?php echo $entrance_date ?></td>
	</tr>
	<tr>
		<th><?php echo __('Entrance fee') ?></th>
		<td><?php echo $entrance_fee ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Monthly instalment of entrance') ?></th>
		<td><?php echo $entrance_fee_payment_rate ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Amount still to be paid') ?></th>
		<td><?php echo $entrance_fee_left ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<?php endif ?>
	<?php if ($devices_fee_left): ?>
	<tr>
		<th colspan="2"><?php echo  __('Device repayments') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Total price') ?></th>
		<td><?php echo $devices_fee ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<?php if ($devices_fee_payment_rate): ?>
	<tr>
		<th><?php echo __('Monthly payment rate') ?></th>
		<td><?php echo $devices_fee_payment_rate ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Amount still to be paid') ?></th>
		<td><?php echo $devices_fee_left ?> <?php echo Settings::get('currency') ?></td>
	</tr>
	<?php endif ?>
</table>

<br />

<?php echo $form ?>