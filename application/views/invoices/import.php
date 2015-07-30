<h2><?php echo __('Import new invoice') ?></h2><br />

<?php echo form::open_multipart(url_lang::base() . 'invoices/import/' . $invoice_template_id) ?>
<table cellspacing="3" class="form">
	<tr>
		<th><?php echo form::label('file', __('File') . ':') ?></th>
		<td><?php echo form::upload('file', ''); ?></td>
	</tr>
	<tr>
		<th></th>
		<td><?php echo form::submit('submit', __('Add'), ' class=submit') ?></td>
	</tr>
</table>
<?php echo form::close() ?>