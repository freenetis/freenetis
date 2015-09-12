<h2><?php echo $headline ?></h2>
<?php
$links = array();
if ($can_edit)
{
	$links[] = html::anchor('api_accounts/edit/' . $api_account->id, __('Edit'),
			array('class' => 'popup_link'));
}
if ($can_delete)
{
	$links[] = html::anchor('api_accounts/delete/' . $api_account->id,
			__('Delete'), array('class' => 'delete_link'));
}
if ($can_view_logs)
{
	$links[] = html::anchor('api_accounts/show_logs/' . $api_account->id,
			__('Show logs'));
}
echo implode(' | ', $links);
?>

<br /><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $api_account->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Enabled') ?></th>
		<td><?php echo callback::boolean($api_account, 'enabled') ?></td>
	</tr>
	<tr>
		<th colspan="2"><?php echo __('Login data')?></th>
	</tr>
	<tr>
		<th><?php echo __('Username') ?></th>
		<td><?php echo $api_account->username ?></td>
	</tr>
	<?php if ($can_view_token): ?>
	<tr>
		<th><?php echo __('Token') ?></th>
		<td>
			<?php echo $api_account->token ?>
			<?php if ($can_reset_token): ?>
				<?php echo html::anchor(
							'api_accounts/token_reset/' . $api_account->id, 
							__('Generate'), array('style' => 'margin-left:15px')); ?>
			<?php endif; ?>
		</td>
	</tr>
	<?php endif; ?>
	<tr>
		<th colspan="2"><?php echo __('Access rights')?></th>
	</tr>
	<tr>
		<th><?php echo __('Readonly') ?></th>
		<td><?php echo callback::boolean($api_account, 'readonly') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Allowed URL paths') ?></th>
		<td><?php echo str_replace(',', "<br/>", $api_account->allowed_paths) ?></td>
	</tr>
</table>
