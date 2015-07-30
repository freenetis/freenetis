<?php if (isset($form)): ?>
<h2><?php echo __('Phone number user') ?></h2><br />
<?php echo $form; ?>
<br />
<?php endif; ?>
<h2><?php echo __('Detail of invoice') ?> <?php echo $phone_invoice->id; ?></h2><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $phone_invoice->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date of issue') ?></th>
		<td><?php echo $phone_invoice->date_of_issuance ?></td>
	</tr>
	<tr>
		<th><?php echo __('Billing period from') ?></th>
		<td><?php echo $phone_invoice->billing_period_from ?></td>
	</tr>
	<tr>
		<th><?php echo __('Billing period to') ?></th>
		<td><?php echo $phone_invoice->billing_period_to ?></td>
	</tr>
	<tr>
		<th><?php echo __('Variable symbol') ?></th>
		<td><?php echo $phone_invoice->variable_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Specific symbol') ?></th>
		<td><?php echo $phone_invoice->specific_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Price out of tax') ?></th>
		<td><?php echo number_format($phone_invoice->total_price, 2, ',', ' ') ?> <?php __(Settings::get('currency')) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Tax') ?></th>
		<td><?php echo number_format($phone_invoice->tax, 2, ',', ' ') ?> <?php __(Settings::get('currency')) ?> / <?php echo $phone_invoice->tax_rate ?>%</td>
	</tr>
	<tr>
		<th><?php echo __('Total price') ?></th>
		<td><b><?php echo number_format($total_price, 2, ',', ' ') ?> <?php __(Settings::get('currency')) ?></b></td>
	</tr>
	<?php if (!$is_payed): ?>
	<?php if (!$phone_invoice->locked && $this->acl_check_new('Phone_invoices_Controller', 'mail_warning')): ?>
	<tr>
		<th><?php echo html::image(array('src' => 'media/images/icons/write_email.png')); ?></th>
		<th><?php echo html::anchor('phone_invoices/post_mail_warning/' . $phone_invoice->id, __('Send mail warning to users related with invoice')) ?></th>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo html::image(array('src' => 'media/images/states/locked.png')); ?></th>
		<th><?php echo html::anchor('phone_invoices/lock_set/' . $phone_invoice->id, $phone_invoice->locked ? __('Unlock invoice') : __('Lock invoice')) ?></th>
	</tr>
	<?php if (Settings::get('finance_enabled') && $phone_invoice->locked): ?>
	<tr>
		<th><?php echo html::image(array('src' => 'media/images/icons/grid_action/money.png')); ?></th>
		<th><?php echo html::anchor('phone_invoices/pay/' . $phone_invoice->id, __('Discount private services from phone keepers credit account')) ?></th>
	</tr>
	<?php endif ?>
	<?php else: ?>
		<th><?php echo html::image(array('src' => 'media/images/icons/grid_action/money.png')); ?></th>
		<th style="color: green; font-weight: bold"><?php echo __('Discounted from credit accounts') ?></th>
	</tr>
	<?php endif ?>
</table><br />

<h2><?php echo __('Phones in invoice') ?></h2><br />
<?php echo $grid ?>
