<h2><?php echo $headline ?></h2><br />
<?php

$links = array();

if ($this->acl_check_edit('Connection_Requests_Controller', 'request') && $connection_request->state == Connection_request_Model::STATE_UNDECIDED)
{
	$links[] = html::anchor('connection_requests/edit/'.$connection_request->id, __('Edit'), array('class' => 'popup_link'));
	$links[] = html::anchor('connection_requests/approve_request/' . $connection_request->id, __('Approve'));
	$links[] = html::anchor('connection_requests/reject_request/'.$connection_request->id, __('Reject'), array('class' => 'confirm_reject'));
}

echo implode(' | ', $links);

if (count($links))
{
	echo '<br /><br />';
}

?>

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $connection_request->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Owner') ?></th>
		<?php if ($this->acl_check_view('Members_Controller', 'members', $connection_request->member_id)): ?>
		<td><?php echo html::anchor('members/show/' . $connection_request->member_id, $connection_request->member->name) ?></td>
		<?php else: ?>
		<td><?php echo $connection_request->member->name ?></td>
		<?php endif; ?>
	</tr>
	<tr>
		<th><?php echo __('Added by') ?></th>
		<?php if ($this->acl_check_view('Users_Controller', 'users', $connection_request->added_user->member_id)): ?>
		<td><?php echo html::anchor('users/show/' . $connection_request->added_user_id, $connection_request->added_user->get_full_name()) ?> (<?php echo $connection_request->created_at ?>)</td>
		<?php else: ?>
		<td><?php echo $connection_request->member->name ?> (<?php echo $connection_request->created_at ?>)</td>
		<?php endif; ?>
	</tr>
	<?php if ($connection_request->decided_user_id): ?>
	<tr>
		<th><?php echo __('Decided by') ?></th>
		<?php if ($this->acl_check_view('Users_Controller', 'users', $connection_request->decided_user->member_id)): ?>
		<td><?php echo html::anchor('users/show/' . $connection_request->decided_user_id, $connection_request->decided_user->get_full_name()) ?> (<?php echo $connection_request->decided_at ?>)</td>
		<?php else: ?>
		<td><?php echo $connection_request->decided_user->get_full_name() ?> (<?php echo $connection_request->decided_at ?>)</td>
		<?php endif; ?>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><b><?php echo callback::connection_request_state_field($connection_request, 'state') ?></b></td>
	</tr>
	<?php if ($connection_request->state == Connection_request_Model::STATE_APPROVED): ?>
	<?php if ($connection_request->device_id): ?>
	<tr>
		<th><?php echo __('Device') ?></th>
		<?php if ($this->acl_check_view('Devices_Controller', 'devices', $connection_request->device->user->member_id)): ?>
		<td><?php echo html::anchor('devices/show/' . $connection_request->device_id, $connection_request->device->name) ?></td>
		<?php else: ?>
		<td><?php echo $connection_request->device->name ?></td>
		<?php endif; ?>
	</tr>
	<?php else: ?>
	<tr>
		<th><?php echo __('Device') ?></th><td><b style="color: red"><?php echo __('Deleted') ?></a></td>
	</tr>
	<?php endif; ?>
	<?php endif; ?>
	<?php if ($connection_request->device_type->id): ?>
	<tr>
		<th><?php echo __('Device type') ?></th>
		<td><?php echo $connection_request->device_type->get_value() ?></td>
	</tr>
	<?php endif; ?>
	<?php if (!empty($connection_request->device_template->name)): ?>
	<tr>
		<th><?php echo __('Device template') ?></th>
		<td><?php echo $connection_request->device_template->name ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo __('MAC address') ?></th>
		<td><?php echo $connection_request->mac_address ?></td>
	</tr>
	<tr>
		<th><?php echo __('Subnet') ?></th>
		<?php if ($this->acl_check_view('Subnets_Controller', 'subnet')): ?>
		<td><?php echo html::anchor('subnets/show/' . $connection_request->subnet_id, $connection_request->subnet->name) ?></td>
		<?php else: ?>
		<td><?php echo $connection_request->subnet->name ?></td>
		<?php endif; ?>
	</tr>
	<tr>
		<th><?php echo __('IP address') ?></th>
		<td><?php echo $connection_request->ip_address ?></td>
	</tr>
	<tr>
		<th><?php echo __('Note') ?></th>
		<td><?php echo $connection_request->comment ?></td>
	</tr>
</table>

<br />
<h3><?php echo __('Comments') ?></h3>

<?php echo $comments_grid ?>
