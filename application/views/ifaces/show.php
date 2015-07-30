<h2><?php echo  $headline ?></h2><br />
<?php

if (isset($submenu))
	echo '<div class="submenu">'.$submenu.'</div>';

$links = array();

if ($this->acl_check_edit('Ifaces_Controller', 'iface', $iface->device->user->member_id))
	$links[] = html::anchor('ifaces/edit/'.$iface->id, __('Edit'));

if ($this->acl_check_delete('Ifaces_Controller', 'iface', $iface->device->user->member_id))
	$links[] = html::anchor('ifaces/delete/'.$iface->id, __('Delete'));

echo implode(' | ', $links);

?>
<br /><br />

<table class="extended" style="float:left; width:360px;" cellspacing="0">
	<tr>
		<th colspan="2"><?php echo __('Interface')?></th>
	</tr>
	<tr>
		<th><?php echo  __('ID') ?></th>
		<td><?php echo  $iface->id ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Interface name') ?></th>
		<td><?php echo  $iface->name ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Comment') ?></th>
		<td><?php echo  $iface->comment ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Type') ?></th>
		<td><?php echo  $iface->get_type() ?></td>
	</tr>
	<?php if (isset($iface->number)): ?>
	<tr>
		<th><?php echo  __('Number') ?></th>
		<td><?php echo $iface->number ?></td>
	</tr>
	<?php endif; ?>
	<?php if (isset($iface->port_mode)): ?>
	<tr>
		<th><?php echo  __('Port mode') ?></th>
		<td><?php echo Iface_Model::get_port_mode($iface->port_mode) ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo  __('Device name') ?></th>
		<td><?php echo html::anchor('devices/show/'.$iface->device->id, $iface->device->name)?></td>
	</tr>
	<?php if (isset($port_vlan->id)): ?>
	<tr>
		<th><?php echo  __('Port VLAN ').help::hint('port_vlan') ?></th>
		<td><?php echo  html::anchor(url_lang::base().'vlans/show/'.$port_vlan->id,$port_vlan->name)  ?></td>
	</tr>
	<?php endif; ?>
	<?php if (Iface_Model::type_has_mac_address($iface->type)): ?>
	<tr>
		<th><?php echo  __('MAC address') ?></th>
		<td><?php echo  $iface->mac ?></td>
	</tr>
	<?php endif; ?>
	<?php if (isset($iface->wireless_mode) && $iface->wireless_mode): ?>
	<tr>
		<th><?php echo  __('Wireless mode') ?></th>
		<td><?php echo Iface_Model::get_wireless_mode($iface->wireless_mode) ?></td>
	</tr>
	<?php endif; ?>
	<?php if (isset($iface->wireless_antenna)): ?>
	<tr>
		<th><?php echo  __('Wireless antenna') ?></th>
		<td><?php echo  Iface_Model::get_wireless_antenna($iface->wireless_antenna)  ?></td>
	</tr>
	<?php endif; ?>
	<?php if (intval($iface->link->id) > 0 && Iface_Model::type_has_link($iface->type)): ?>
	<tr>
		<th colspan="2"><?php echo __('Link') ?></th>
	</tr>
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $iface->link->id ?> </td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<?php if ($this->acl_check_view('Links_Controller', 'link')): ?>
		<td><?php echo html::anchor('links/show/'.$iface->link->id, $iface->link->name) ?></td>
		<?php else: ?>
		<td><?php echo $iface->link->name ?></td>
		<?php endif ?>
	</tr>
	<tr>
		<th><?php echo __('Medium') ?></th>
		<td><?php echo Link_Model::get_medium_type($iface->link->medium) ?></td>
	</tr>
	<?php if ($iface->link->medium == Link_Model::MEDIUM_AIR && !empty($iface->link->wireless_ssid)): ?>
	<tr>
		<th><?php echo __('SSID') ?></th>
		<td><?php echo $iface->link->wireless_ssid ?></td>
	</tr>
	<?php endif; ?>
	<?php endif; ?>
</table>
<?php if ($iface->type == Iface_model::TYPE_VLAN): ?>
<table class="extended" style="float:left; margin-left:10px; width:360px;" cellspacing="0">
	<tr>
		<th colspan="2"><?php echo __('VLAN detail')?></th>
	</tr>
	<?php
	foreach ($iface->ifaces_relationships AS $parent): ?>
	<tr>
		<th><?php echo __('Parent interface') ?></th>
		<td><?php echo html::anchor('ifaces/show/'.$parent->parent_iface->id, $parent->parent_iface) ?></td>
	</tr>
	<?php endforeach;
	foreach ($iface->ifaces_vlans AS $vlan): ?>
	<tr>
		<th><?php echo __('VLAN') ?></th>
		<td><?php echo html::anchor('vlans/show/'.$vlan->vlan->id, $vlan->vlan->name) ?></td>
	</tr>
	<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($iface->type == Iface_model::TYPE_VIRTUAL_AP): ?>
<table class="extended" style="float:left; margin-left:10px; width:360px;" cellspacing="0">
	<tr>
		<th colspan="2"><?php echo __('Virtual AP detail')?></th>
	</tr>
	<?php
	foreach ($iface->ifaces_relationships AS $parent): ?>
	<tr>
		<th><?php echo __('Parent wireless interface') ?></th>
		<td><?php echo html::anchor('ifaces/show/'.$parent->parent_iface->id, $parent->parent_iface) ?></td>
	</tr>
	<?php endforeach; ?>

</table>
<?php endif; ?>

<br class="clear" />
<br />

<?php echo $detail ?>

<?php if (!empty($child_ifaces)):?>
<h3><?php echo __('Virtual interfaces created above this interface') ?></h3>

<?php echo $child_ifaces; 
endif; ?>

<br class="clear" />
<br />

