<h2><?php echo $headline ?></h2><br />
<?php

$links = array();

if ($this->acl_check_edit('Log_queues_Controller', 'log_queue') && ($log_queue->state != Log_queue_Model::STATE_CLOSED))
{
	$links[] = html::anchor('log_queues/close_log/'.$log_queue->id, __('Set state closed'));
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
		<td><?php echo $log_queue->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td><b style="color:white; padding:2px; background-color:<?php echo Log_queue_Model::get_type_color($log_queue->type) ?>"><?php echo Log_queue_Model::get_type_name($log_queue->type) ?></b></td>
	</tr>
	<tr>
		<th><?php echo __('Created at') ?></th>
		<td><?php echo $log_queue->created_at ?></td>
	</tr>
	<tr>
		<th><?php echo __('Description') ?></th>
		<td><?php echo $log_queue->description ?></td>
	</tr>
	<?php if ($log_queue->exception_backtrace): ?>
	<tr>
		<th><?php echo ($log_queue->type == Log_queue_Model::TYPE_INFO) ? __('Message') : __('Exception') ?></th>
		<td><?php echo nl2br($log_queue->exception_backtrace, TRUE) ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><b><?php echo Log_queue_Model::get_state($log_queue->state) ?></b></td>
	</tr>
	<?php if ($log_queue->closed_by_user_id): ?>
	<tr>
		<th><?php echo __('Closed by') ?></th>
		<?php if ($this->acl_check_view('Users_Controller', 'users', $log_queue->closed_by_user->member_id)): ?>
		<td><?php echo html::anchor('users/show/' . $log_queue->closed_by_user_id, $log_queue->closed_by_user->get_full_name()) ?> (<?php echo $log_queue->closed_at ?>)</td>
		<?php else: ?>
		<td><?php echo $log_queue->closed_by_user->get_full_name() ?> (<?php echo $log_queue->closed_at ?>)</td>
		<?php endif; ?>
	</tr>
	<?php endif; ?>
</table>

<br />
<h3><?php echo __('Comments') ?></h3>

<?php echo $comments_grid ?>
