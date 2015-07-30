<h2><?php echo __('Detail of access control group of users') ?></h2>
<br />
<?php

$links = array();

if ($this->acl_check_edit('Aro_groups_Controller', 'aro_group'))
	$links[] = html::anchor('aro_groups/edit/'.$aro_group->id, __('Edit'));

if ($this->acl_check_delete('Aro_groups_Controller', 'aro_group'))
	$links[] = html::anchor('aro_groups/delete/'.$aro_group->id, __('Delete'), array('class' => 'delete_link'));

echo implode(" | ",$links);
?>
<br /><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $aro_group->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<td><?php echo $aro_group->name ?></td>
	</tr>
<?php
if ($parent && $parent->id)
{
?>
<tr>
	<th><?php echo __('Parent') ?></th>
	
	<?php if ($parent->id != Aro_group_Model::ALL): ?>
		<?php if ($this->acl_check_view('Aro_groups_Controller', 'aro_group')): ?>
			<td><?php echo html::anchor('aro_groups/show/'.$parent->id, $parent->name) ?></td>
		<?php else: ?>
			<td><?php echo __($parent->name) ?></td>
		<?php endif ?>
	<?php else: ?>
		<td><?php echo __($parent->name) ?></td>
	<?php endif ?>
</tr>
<?php
}
?>
</table>
<br /><br />

<h3><?php echo __('ARO') ?> <?php echo help::hint('aro') ?></h3>
<?php echo $aro_grid ?>
<br />

<h3><?php echo __('ACL') ?> <?php echo help::hint('acl') ?></h3>
<?php echo $acl_grid ?>