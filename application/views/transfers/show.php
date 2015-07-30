<h2><?php echo $headline ?></h2>
<br />
<?php

if ($this->acl_check_view('Accounts_Controller', 'transfers')):

	$links = array();
	$links[] = html::anchor('transfers/show_all', __('Back to day book'));

	if (isset($bt))
	{
		$links[] = html::anchor('bank_transfers/show_by_bank_statement/'.$bt->bank_statement_id, __('Back to bank statement'));
	}

	echo implode(" | ", $links);
?>
<br />
<?php endif; ?>
<br />
<table class="extended" cellspacing="0" style="float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Transfer') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Transfer ID')?></th>
		<td><?php echo $transfer->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Origin account') ?></th>
		<td><?php echo html::anchor('transfers/show_by_account/'.$transfer->oa_id, $transfer->oa_name); ?></td>
	</tr>
	<tr>
		<th><?php echo __('Destination account') ?></th>
		<td><?php echo html::anchor('transfers/show_by_account/'.$transfer->da_id, $transfer->da_name); ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date and time') ?></th>
		<td><?php echo $transfer->datetime; ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date and time of creation') ?></th>
		<td><?php echo $transfer->creation_datetime; ?></td>
	</tr>
	<tr>
		<th><?php echo __('Text') ?></th>
		<td><?php echo $transfer->text; ?></td>
	</tr>
	<tr>
		<th><?php echo __('Amount') ?></th>
		<td><?php echo number_format((float)$transfer->amount, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php if (isset($transfer->user_id)) { ?>
	<tr>
		<th><?php echo __('Added by') ?></th>
		<td><?php echo html::anchor('users/show/'.$transfer->user_id, $transfer->name.' '.$transfer->surname) ?></td>	
	</tr>
	<?php } ?>
	<?php if (isset($transfer->job_report_id)) { ?>
	<tr>
		<th><?php echo __('Work report') ?></th>
		<td><?php echo html::anchor('work_reports/show/'.$transfer->job_report_id, $transfer->job_report_description) ?></td>
	</tr>
	<?php } else if (isset($transfer->job_id)) { ?>
	<tr>
		<th><?php echo __('Work') ?></th>
		<td><?php echo html::anchor('works/show/'.$transfer->job_id, $transfer->job_description) ?></td>	
	</tr>
	<?php } ?>	
	<?php if (isset($transfer->previous_transfer_id))	{ ?>
	<tr>
		<th><?php echo __('Previous transfer') ?></th>
		<td><?php echo html::anchor('transfers/show/'.$transfer->previous_transfer_id, $transfer->previous_transfer_id) ?></td>
	</tr>
	<?php }	?>
	<?php ?>
	<?php foreach ($dependent_transfers as $dep) { ?>
	<tr>
		<th><?php echo __('Dependent transfer') ?></th>
		<td><?php echo html::anchor('transfers/show/'.$dep->id, $dep->id) ?></td>
	</tr>	
	<?php } ?>
</table>
<?php if (isset($bt)) { ?>
<table class="extended" style="float:left;width:360px; margin-left:10px;">	
	<tr>
		<th colspan="2"><?php echo __('Bank transfer') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Bank transfer ID') ?></th>
		<td><?php echo $bt->bt_id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Transaction code') ?></th>
		<td><?php echo $bt->transaction_code ?></td>
	</tr>
	<tr>
		<th><?php echo __('Constant symbol') ?></th>
		<td><?php echo $bt->constant_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Variable symbol') ?></th>
		<td><?php echo $bt->variable_symbol ?></td>
	</tr>
	<tr>
		<th><?php echo __('Specific symbol') ?></th>
		<td><?php echo $bt->specific_symbol ?></td>
	</tr>	
	<tr>
		<th colspan="2"><?php echo __('Origin bank account') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Bank account id') ?></th>
		<td><?php echo empty($bt->oba_id) ? '' : html::anchor('bank_transfers/show_by_bank_account/'.$bt->oba_id, $bt->oba_id) ?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account name') ?></th>
		<td><?php echo $bt->oba_name ?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account number') ?></th>
		<td><?php echo $bt->oba_number ?></td>
	</tr>
	<tr>
		<th colspan="2"><?php echo __('Destination bank account') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Bank account id') ?></th>
		<td><?php echo empty($bt->dba_id) ? '' : html::anchor('bank_transfers/show_by_bank_account/'.$bt->dba_id, $bt->dba_id)?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account name') ?></th>
		<td><?php echo $bt->dba_name ?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account number') ?></th>
		<td><?php echo $bt->dba_number ?></td>
	</tr>
</table>	
<?php } ?>
