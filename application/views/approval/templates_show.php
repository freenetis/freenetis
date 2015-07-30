<h2><?php echo __('Show approval template') ?></h2>
<?php
$links = array();

if ($this->approval_template->state < 2 && $this->acl_check_edit('approval', 'templates'))
	$links[] = html::anchor('approval_templates/edit/' . $approval_template->id, __('Edit'));

if ($this->approval_template->state < 1 && $this->acl_check_delete('approval', 'templates'))
	$links[] = html::anchor('approval_templates/delete/' . $approval_template->id, __('Delete'), array('class' => 'delete_link'));

echo implode(' | ', $links);
?>
<br /><br />
<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $approval_template->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<td><?php echo $approval_template->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Comment') ?></th>
		<td><?php echo $approval_template->comment ?></td>
	</tr>
</table>
<br /><br />
<h3><?php echo __('Template items') ?></h3>
<?php echo $items_grid ?>