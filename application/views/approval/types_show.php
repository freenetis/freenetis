<h2><?php echo __('Show approval type') ?></h2>
<?php
$links = array();

if ($state < 2 && $this->acl_check_edit('approval', 'types'))
	$links[] = html::anchor('approval_types/edit/' . $approval_type->id, __('Edit'));

if ($state < 1 && $this->acl_check_delete('approval', 'types'))
	$links[] = html::anchor('approval_types/delete/' . $approval_type->id, __('Delete'), array('class' => 'delete_link'));

echo implode(' | ', $links);
?>
<br /><br />
<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $approval_type->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<td><?php echo $approval_type->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Comment') ?></th>
		<td><?php echo $approval_type->comment ?></td>
	</tr>
	<tr>
		<th><?php echo __('Group') ?></th>
		<td><?php echo __('' . $approval_type->aro_group->name) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Minimal suggest amount') ?></th>
		<td><?php echo money::format($approval_type->min_suggest_amount) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td><?php echo Approval_type_Model::get_type_name($approval_type->type) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Percent for majority') ?> <?php echo help::hint('approval_type_min_suggested_amount') ?></th>
		<td><?php echo $approval_type->majority_percent ?>%</td>
	</tr>
	<tr>
		<th><?php echo __('Single vote is enough?') ?> <?php echo help::hint('approval_type_one_vote') ?></th>
		<td><?php echo ($approval_type->one_vote) ? __('Yes') : __('No') ?></td>
	</tr>
	<?php /* TODO: #815
	<tr>
		<th><?php echo __('Interval') ?></th>
		<td><?php
				$interval = date::interval($approval_type->interval);
				echo $interval['h'] . ' ' . strtolower(__('hours')) 
			?>
		</td>
	</tr>
	<tr>
		<th><?php echo __('Default vote') ?></th>
		<td><?php echo Vote_Model::get_vote_option_name($approval_type->default_vote, TRUE); ?></td>
	</tr>
	 */ ?>
</table>