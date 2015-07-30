<h2><?php echo $headline ?></h2>
<br />

<?php

$links = array();

if ($this->acl_check_edit('Device_active_links_Controller', 'active_links')) 
	$links[] = html::anchor('device_active_links/edit/'.$active_link->id, __('Edit'));

if ($this->acl_check_delete('Device_active_links_Controller', 'active_links')) 
	$links[] = html::anchor('device_active_links/delete/'.$active_link->id, __('Delete'), array('class' => 'delete_link'));

echo implode(' | ', $links)
?>
<br />
<br />

<table class="extended clear" cellspacing="0" style="float:left;word-wrap: break-word;">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $active_link->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('URL pattern') ?></th>
		<td><?php echo $active_link->url_pattern ?></td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<td><?php echo $active_link->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Title') ?></th>
		<td><?php echo $active_link->title ?></td>
	</tr>
	<tr>
		<th><?php echo __('Send as form') ?></th>
		<td><?php echo callback::boolean($active_link, "as_form") ?></td>
	</tr>
	<tr>
		<th><?php echo __('Show in user grid') ?></th>
		<td><?php echo callback::boolean($active_link, "show_in_user_grid") ?></td>
	</tr>
	<tr>
		<th><?php echo __('Show in grid') ?></th>
		<td><?php echo callback::boolean($active_link, "show_in_grid") ?></td>
	</tr>
	
</table>

<div class="clear"></div>
<br />

<h3><?php echo __('Devices') ?></h3>

<div id="devices-grid">
	<?php echo $devices_grid ?>
</div>

<br />
<h3><?php echo __('Device templates') ?></h3>

<div id="devices-grid">
	<?php echo $device_templates_grid ?>
</div>