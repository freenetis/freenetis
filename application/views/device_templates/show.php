<h2><?php echo __('Device template') ?> - <?php echo $device_template->name ?></h2>
<?php

$arr_links = array();

if ($this->acl_check_edit('Devices_Controller', 'devices'))
	$arr_links[] = html::anchor('device_templates/edit/' . $device_template->id, __('Edit'));

if ($this->acl_check_delete('Devices_Controller', 'devices'))
	$arr_links[] =  html::anchor('device_templates/delete/' . $device_template->id, __('Delete'), array('class' => 'delete_link'));

echo implode(' | ', $arr_links);

?>

<br /><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Trade name') ?></th>
		<td><?php echo $device_template->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td><?php echo $device_template->enum_type->get_value() ?></td>
	</tr>
	<tr>
		<th><?php echo __('Default') ?></th>
		<td><?php echo $device_template->default ? __('Yes') : __('No') ?></td>
	</tr>
</table>

<br /><h3><?php echo __('Ethernet interfaces') ?></h3>

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Count') ?></th>
		<td><?php echo $ivals[Iface_Model::TYPE_ETHERNET]['count'] ?></td>
	</tr>
	<tr>
		<th><?php echo __('Names') ?></th>
		<td><?php foreach ($ivals[Iface_Model::TYPE_ETHERNET]['items'] as $item) echo ($item['name']) ? $item['name'] . ', ' : '' ?></td>
	</tr>
</table>

<br /><h3><?php echo __('Wireless interfaces') ?></h3>

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Minimal count') ?></th>
		<td><?php echo $ivals[Iface_Model::TYPE_WIRELESS]['min_count'] ?></td>
	</tr>
	<tr>
		<th><?php echo __('Maximal count') ?></th>
		<td><?php echo $ivals[Iface_Model::TYPE_WIRELESS]['max_count'] ?></td>
	</tr>
</table>

<br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('Wireless mode') ?></th>
		<th><?php echo __('Wireless antenna') ?></th>
	</tr>
	<?php foreach ($ivals[Iface_Model::TYPE_WIRELESS]['items'] as $item): ?>
	<tr>
		<td><?php echo $item['name'] ?></td>
		<td><?php echo $iface_model->get_wireless_mode(@$item['wireless_mode']) ?></td>
		<td><?php echo $iface_model->get_wireless_antenna(@$item['wireless_antenna']) ?></td>
	</tr>
	<?php endforeach; ?>
</table>

<br /><h3><?php echo __('Ports') ?></h3>

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Count') ?></th>
		<td><?php echo $ivals[Iface_Model::TYPE_PORT]['count'] ?></td>
	</tr>
</table>

<br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('Port number') ?></th>
		<th><?php echo __('Port mode') ?></th>
	</tr>
	<?php foreach ($ivals[Iface_Model::TYPE_PORT]['items'] as $item): ?>
	<tr>
		<td><?php echo $item['name'] ?></td>
		<td><?php echo __('Port') ?> <?php echo $item['number'] ?></td>
		<td><?php echo $iface_model->get_port_mode($item['port_mode']) ?></td>
	</tr>
	<?php endforeach; ?>
</table>

<br /><h3><?php echo __('Internal interfaces') ?></h3>

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Count') ?></th>
		<td><?php echo $ivals[Iface_Model::TYPE_INTERNAL]['count'] ?></td>
	</tr>
	<tr>
		<th><?php echo __('Names') ?></th>
		<td><?php foreach ($ivals[Iface_Model::TYPE_INTERNAL]['items'] as $item) echo ($item['name']) ? $item['name'] . ', ' : '' ?></td>
	</tr>
</table>
