<h2><?php echo $headline ?></h2>
<?php echo html::anchor('invoices/edit/' . $invoice->id, __('Edit')) ?>
&nbsp;
<?php echo html::anchor('invoices/export_single/' . $invoice->id, __('Export')) ?>
<br /><br />

<table class="extended" cellspacing="0" style="float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Basic information') ?></th>
	</tr>
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $invoice->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Partner') ?></th>
		<td><?php	if (!empty($partner))
						echo html::anchor('members/show/' . $partner->id, $partner->name);
					elseif (!empty($invoice->partner_company))
						echo $invoice->partner_company;
					else
						echo $invoice->partner_name; ?></td>
	</tr>
	<tr>
		<th><?php echo __('Invoice number') ?></th>
		<td><?php echo $invoice->invoice_nr ?></td>
	</tr>
	<tr>
		<th><?php echo __('Invoice type') ?></th>
		<td><?php echo callback::invoice_type_field($invoice, 'invoice_type') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Account number') ?></th>
		<td><?php echo $invoice->account_nr ?></td>
	</tr>
	<tr>
		<th><?php echo __('Variable symbol') ?></th>
		<td><?php echo !empty($invoice->var_sym) ? $invoice->var_sym : '' ?></td>
	</tr>
	<tr>
		<th><?php echo __('Constant symbol') ?></th>
		<td><?php echo !empty($invoice->con_sym) ? $invoice->con_sym : '' ?></td>
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
		<td><?php echo $invoice->vat ? __('Yes') : __('No') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Order number') ?></th>
		<td><?php echo !empty($invoice->order_nr) ? $invoice->order_nr : '' ?></td>
	</tr>
	<tr>
		<th><?php echo __('Currency') ?></th>
		<td><?php echo $invoice->currency ?></td>
	</tr>
	<tr>
		<th colspan="2"><?php echo  __('Note') ?></th>
	</tr>
	<tr>
		<td colspan="2"><?php echo $invoice->note ?></th>
	</tr>
</table>

<?php if (empty($partner)) 
	{?>
	<table class="extended" cellspacing="0" style="float:left; margin-left:10px; width:360px;">
		<tr>
			<th colspan="2"><?php echo  __('Contact information') ?></th>
		</tr>
		<?php 
		if (!empty($invoice->partner_company))
		{ ?>
		<tr>
			<th><?php echo __('Company') ?></th>
			<td><?php echo ($invoice->partner_company) ?></td>
		</tr>
	<?php
		}
	?>
	<?php 
		if (!empty($invoice->partner_name))
		{ ?>
		<tr>
			<th><?php echo __('Name') ?></th>
			<td><?php echo ($invoice->partner_name) ?></td>
		</tr>
	<?php
		}
	?>	
		<tr>
			<th><?php echo __('Street') ?></th>
			<td><?php echo ($invoice->partner_street) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Street number') ?></th>
			<td><?php echo ($invoice->partner_street_number) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Town') ?></th>
			<td><?php echo ($invoice->partner_town) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Zip code') ?></th>
			<td><?php echo ($invoice->partner_zip_code) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Country') ?></th>
			<td><?php echo ($invoice->partner_country) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Organization identifier') ?></th>
			<td><?php echo ($invoice->organization_identifier) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Phone') ?></th>
			<td><?php echo ($invoice->phone_number) ?></td>
		</tr>
		<tr>
			<th><?php echo __('Email') ?></th>
			<td><?php echo ($invoice->email) ?></td>
		</tr>
	</table><br />
	<?php	} ?>

<div class="clear"></div>

<br />
<h3><?php echo __('Invoice items') ?></h3>
<?php echo html::anchor('invoice_items/add/' . $invoice->id, __('Add new items'), array('title' => __('Add new items'), 'class' => 'popup_link')) ?>
<?php echo form::close() ?>
<br /><br />
<?php echo $grid ?>