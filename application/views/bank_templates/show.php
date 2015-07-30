<h2><?php echo $headline ?></h2>
<br />
<?php echo html::anchor('bank_templates/edit/' . $template->id, __('Edit')); ?>
<br />
<br />

<table class="extended">
	<tr>
		<th colspan="2"><?php echo __('Basic information') ?></th>
	</tr>
	<tr>
		<th>ID</th>
		<td><?php echo $template->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Template name') ?></th>
		<td><?php echo $template->template_name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Item separator') ?></th>
		<td><?php echo $template->item_separator ?></td>
	</tr>
	<tr>
		<th><?php echo __('String separator') ?></th>
		<td><?php echo $template->string_separator ?></td>
	</tr>
</table>

<br />

<table class="extended">
	<tr>
		<th colspan="2"><?php echo __('Column headers') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Account name') ?></th>
		<td><?php echo $template->account_name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Account number') ?></th>
		<td><?php echo $template->account_number ?></td>
	</tr>
	<tr>
		<th><?php echo __('Bank code') ?></th>
		<td><?php echo $template->bank_code ?></td>
	</tr>
	<tr>
		<th><?php echo __('Constant symbol') ?></th>
		<td><?php echo $template->constant_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Variable symbol') ?></th>
		<td><?php echo $template->variable_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Specific symbol') ?></th>
		<td><?php echo $template->specific_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Counteraccount name') ?></th>
		<td><?php echo $template->counteraccount_name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Counteraccount number') ?></th>
		<td><?php echo $template->counteraccount_number ?></td>
	</tr>
	<tr>
		<th><?php echo __('Counteraccount bank code') ?></th>
		<td><?php echo $template->counteraccount_bank_code ?></td>
	</tr>
	<tr>
		<th><?php echo __('Text') ?></th>
		<td><?php echo $template->text ?></td>
	</tr>
	<tr>
		<th><?php echo __('Amount') ?></th>
		<td><?php echo $template->amount ?></td>
	</tr>
	<tr>
		<th><?php echo __('Expenditure-earning') ?></th>
		<td><?php echo $template->expenditure_earning ?></td>
	</tr>
	<tr>
		<th><?php echo __('Value for earning') ?></th>
		<td><?php echo $template->value_for_earning ?></td>
	</tr>		
	<tr>
		<th><?php echo __('Date and time') ?></th>
		<td><?php echo $template->datetime ?></td>
	</tr>
</table>
