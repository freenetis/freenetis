<h2><?php echo $headline ?></h2><br />
<?php echo html::anchor('invoice_items/edit/' . $invoice_item->id, __('Edit')) ?>
<br /><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $invoice_item->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Invoice number') ?></th>
		<td><?php echo html::anchor('invoices/show/' . $invoice_item->invoice_id, $invoice_item->invoice->invoice_nr) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<td><?php echo $invoice_item->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Code') ?></th>
		<td><?php echo $invoice_item->code ?></td>
	</tr>
	<tr>
		<th><?php echo __('Quantity') ?></th>
		<td><?php echo $invoice_item->quantity ?></td>
	</tr>
	<tr>
		<th><?php echo __('Author fee') ?></th>
		<td><?php echo $invoice_item->author_fee ?></td>
	</tr>
	<tr>
		<th><?php echo __('Contractual increase') ?></th>
		<td><?php echo $invoice_item->contractual_increase ?></td>
	</tr>
	<tr>
		<th><?php echo __('Service') ?></th>
		<td><?php echo $invoice_item->service ?></td>
	</tr>
	<tr>
		<th><?php echo __('Price') ?></th>
		<td><?php echo callback::money($invoice_item, 'price') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Tax rate') ?></th>
		<td><?php echo callback::percent2($invoice_item, 'vat') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Price vat') ?></th>
		<td><?php echo callback::price_vat_field($invoice_item, 'price') ?></td>
	</tr>
</table>