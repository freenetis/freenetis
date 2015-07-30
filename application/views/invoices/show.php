<h2><?php echo $headline ?></h2>
<?php echo html::anchor('invoices/edit/' . $invoice->id, __('Edit')) ?>
<br /><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $invoice->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Supplier') ?></th>
		<td><?php echo html::anchor('members/show/' . $supplier->id, $supplier->name) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Invoice number') ?></th>
		<td><?php echo $invoice->invoice_nr ?></td>
	</tr>
	<tr>
		<th><?php echo __('Variable symbol') ?></th>
		<td><?php echo $invoice->var_sym ?></td>
	</tr>
	<tr>
		<th><?php echo __('Constant symbol') ?></th>
		<td><?php echo $invoice->con_sym ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date of issue') ?></th>
		<td><?php echo $invoice->date_inv ?></td>
	</tr>
	<tr>
		<th><?php echo __('Due date') ?></th>
		<td><?php echo $invoice->date_due ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date vat') ?></th>
		<td><?php echo $invoice->date_vat ?></td>
	</tr>
	<tr>
		<th><?php echo __('Vat') ?></th>
		<td><?php echo ($invoice->vat) ? __('Yes') : __('No') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Order number') ?></th>
		<td><?php echo $invoice->order_nr ?></td>
	</tr>
	<tr>
		<th><?php echo __('Currency') ?></th>
		<td><?php echo $invoice->currency ?></td>
	</tr>
</table><br />

<h3><?php echo __('Invoice items') ?></h3>
<?php echo __('Add new items') . ':' ?>
<?php echo form::open(url_lang::base() . 'invoices/show/' . $invoice->id, array('style' => 'display: inline')) ?>
<?php echo form::input('item_count', '1', ' style="width: 35px"') ?>
<?php echo form::submit('submit', __('Add'), ' class=submit style="width: 50px"') ?>
<?php echo form::close() ?>
<br /><br />
<?php echo $grid ?>