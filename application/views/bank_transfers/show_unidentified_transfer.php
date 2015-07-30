<h2><?php echo $heading ?></h2>
<br />
<table class="extended" style="float:left">
	<tr>
		<th colspan="2"><?php echo  __('Transfer information') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Transfer ID')?></th>
		<td><?php echo $mt->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date and time') ?></th>
		<td><?php echo $mt->datetime ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date and time of creation') ?></th>
		<td><?php echo $mt->creation_datetime ?></td>
	</tr>	
	<tr>
		<th><?php echo __('Text') ?></th>
		<td><?php echo $mt->text ?></td>
	</tr>
	<tr>
		<th><?php echo __('Amount') ?></th>
		<td><?php echo number_format((float)$mt->amount, 2, ',', ' ') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Bank transfer ID')?></th>
		<td><?php echo $mt->bt_id ?></td>
	</tr>	
	<tr>
		<th><?php echo __('Variable symbol') ?></th>
		<td><?php echo $mt->variable_symbol ?></td>
	</tr>
</table>
<table class="extended" style="float:left; margin-left: 10px">	
	<tr>
		<th colspan="2"><?php echo __('Origin account') ?> </th>
	</tr>
	<tr>
		<th><?php echo __('Owner of account') ?> </th>
		<td><?php echo isset($mt->oba_member_id) ? html::anchor('members/show/'.$mt->oba_member_id, $mt->oba_member_name) : ''?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account name') ?> </th>
		<td><?php echo $mt->oba_name?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account number') ?> </th>
		<td><?php echo html::anchor('bank_transfers/show_by_bank_account/'.$mt->oba_id, $mt->oba_number)?></td>
	</tr>
	<tr>
		<th colspan="2"><?php echo __('Destination account') ?> </th>
	</tr>
	<tr>
		<th><?php echo __('Owner of account') ?> </th>
		<td><?php echo isset($mt->dba_member_id) ? html::anchor('members/show/'.$mt->dba_member_id, $mt->dba_member_name) : ''?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account name') ?> </th>
		<td><?php echo $mt->dba_name?></td>
	</tr>	
	<tr>
		<th><?php echo __('Account number') ?> </th>
		<td><?php echo $mt->dba_number?></td>
	</tr>
</table>
<br class="clear" />
<br />

<h3><?php echo __('Options for assigning (match) transfer') ?></h3>

<p style="margin: 5px 2px 15px 2px;"><?php echo url_lang::lang('help.assign_payment') ?></p>

<a href="<?php echo url_lang::site() ?>/bank_transfers/assign_member_transfer/<?php echo $mt->id ?>"
   class="submit" style="text-decoration: none; color: white">
    <?php echo __('Transfer is member payment') ?>
</a>

<br />
<br />

<a href="<?php echo url_lang::site() ?>/bank_transfers/assign_other_transfer/<?php echo $mt->id ?>" 
   class="submit" style="text-decoration: none; color: white">
    <?php echo __('Transfer is not member payment') ?>
</a>