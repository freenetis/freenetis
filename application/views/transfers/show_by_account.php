<h2><?php echo $headline ?></h2><br />
<?php
$links = array();

if ($this->acl_check_view ('Members_Controller', 'comment', $account->member_id))
{
	$links[] = html::anchor('transfers/show_by_account/'.$account->id.'#transfers', __('Transfers'), array('id' => 'transfers_link'));
	$links[] = html::anchor('transfers/show_by_account/'.$account->id.'#comments', __('Comments'), array('id' => 'comments_link'));
}

if ($links): 
echo implode(' | ', $links); ?>
<br />
<br />
<?php endif; ?>
<table class="extended" style="float:left">
	<tr>
		<th><?php echo __('Account ID')?></th>
		<td><?php echo $account->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Account name')?></th>
		<td><?php echo $account->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Owner of account')?></th>
		<td><?php if ($account->member_id) { echo html::anchor('members/show/'.$account->member_id, $account->member->name); } ?></td>
	</tr>
	<tr>
		<th><?php echo __('Type of double-entry account')?></th>
		<td><?php echo $account->account_attribute->name ?></td>
	</tr>
	<?php if ($account->account_attribute_id == Account_attribute_Model::CREDIT) { ?>
	<tr>
		<th><?php echo __('Entrance date')?></th>
		<td><?php echo $account->member->entrance_date ?></td>
	</tr>
		<?php if ($account->member->leaving_date > '0000-00-00') {?>
		<tr>
			<th><?php echo __('Leaving date')?></th>
			<td><?php echo $account->member->leaving_date ?></td>
		</tr>
		<?php } ?>
	<?php } ?>
</table>

<table class="extended" style="float:left; margin-left:10px">
	<tr>
		<th><?php echo __('Balance')?></th>
		<td><?php echo number_format((float)$balance, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Total inbound')?></th>
		<td><?php echo number_format((float)$inbound, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Total outbound')?></th>
		<td><?php echo number_format((float)$outbound, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php if ($account->account_attribute_id == Account_attribute_Model::CREDIT) { ?>
	<tr>
		<th><?php echo __('Variable symbols')?></th>
		<td>
		    <?php foreach ($variable_symbols as $i => $variable_s):?>
			<?php echo  $variable_s->variable_symbol ?><br />
		    <?php endforeach; ?>
		</td>
	</tr>
	<?php if (isset($expiration_date)) { ?>
	<tr>
		<th><?php echo __('Payed to')?></th>
		<td><?php echo $expiration_date ?></td>
	</tr>
	<?php } ?>
	<?php } ?>
</table>

<br class="clear" />
<br />

<div id="transfers_grid">
	<h3><a name="transfers"><?php echo __('Transfers') ?></a></h3>
	<?php echo $transfers_grid; ?><br />
</div>

<?php if ($this->acl_check_view ('Members_Controller','comment',$account->member_id)): ?>
<div id="comments_grid">
	<h3><a name="comments"><?php echo __('Comments') ?></a></h3>
	<?php echo $comments_grid; ?>
</div>
<?php endif ?>