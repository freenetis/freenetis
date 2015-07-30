<h2><?php echo $headline ?></h2>
<br />
<table class="extended" style="float:left">
	<tr>
		<th>ID</th>
		<td><?php echo $ba->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Account number')?></th>
		<td><?php echo $ba->account_nr ?></td>
	</tr>
	<tr>
		<th><?php echo __('Bank code')?></th>
		<td><?php echo $ba->bank_nr ?></td>
	</tr>
</table>
<table class="extended" style="float:left; margin-left:10px">
	<tr>
		<th><?php echo __('Account name')?></th>
		<td><?php echo $ba->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('IBAN')?></th>
		<td><?php echo $ba->IBAN ?></td>
	</tr>
	<tr>
		<th><?php echo __('SWIFT')?></th>
		<td><?php echo $ba->SWIFT ?></td>
	</tr>
</table>
<br class="clear" />
<br />
<?php echo $grid ?>