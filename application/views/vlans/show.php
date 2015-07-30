<h2><?php echo $headline ?></h2>
<?php if (isset($submenu))
	echo '<div class="submenu">' . $submenu . '</div>'; ?>
<?php
if ($this->acl_check_edit('Vlans_Controller', 'vlan'))
	echo html::anchor('vlans/edit/' . $vlan->id, __('Edit'))
?>
<?php
if ($this->acl_check_delete('Vlans_Controller', 'vlan'))
	echo ' | '.html::anchor('vlans/delete/' . $vlan->id, __('Delete'))
?><br /><br />


<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $vlan->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Vlan name') ?></th>
		<td><?php echo $vlan->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('tag_802_1q') ?></th>
		<td><?php echo $vlan->tag_802_1q ?></td>
	</tr>

	<tr>
		<th><?php echo __('Comment') ?></th>
		<td><?php echo $vlan->comment ?></td>
	</tr>

</table><br /><br />

<h3><?php echo __('Devices') ?></h3><br />
<?php echo $grid ?>
<br />				

