<h2><?php echo __('Device') ?> <?php echo $device->name ?></h2>
<br />
<?php

$links = array();

if ($this->acl_check_edit(get_class($this), 'devices')) 
	$links[] = html::anchor('devices/edit/'.$device->id, __('Edit'));

if ($this->acl_check_delete('Devices_Controller', 'devices'))
	$links[] = html::anchor('devices/delete/'.$device->id, __('Delete'), array('class' => 'delete_link'));

if (Settings::get('syslog_ng_mysql_api_enabled'))
	$links[] = html::anchor('device_logs/show_by_device/'.$device->id, __('Show logs'));

$links[] = html::anchor('devices/export/'.$device->id, __('Export'));

$links[] = html::anchor('devices/map/'.$device->id, __('Show subdevices tree'));

if (Settings::get('monitoring_enabled'))
{
	$links[] = html::anchor('monitoring/action/'.$device->id, __('Monitoring'), array
	(
		'title' => __('Monitoring'),
		'class' => 'popup_link'
	));
}

echo implode(' | ', $links)
?>
<br />
<br />

<table class="extended" cellspacing="0" style="float:left; width: 400px; word-wrap: break-word;">
	<tr>
		<th><?php echo __('Device ID') ?></th>
		<td><?php echo $device->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Member') ?></th>
		<td><?php echo html::anchor('members/show/'.$device->user->member_id, $device->user->member->name.' ('.$device->user->member_id.')'); ?></td>
	</tr>
	<tr>
		<th><?php echo __('User') ?></th>
		<td><?php echo html::anchor('users/show/'.$device->user_id, $device->user->get_full_name()); ?></td>
	</tr>
	<tr>
		<th><?php echo __('Trade name') ?></th>
		<td><?php echo $device->trade_name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td><?php echo $device_type ?></td>
	</tr>
	<?php if ($this->acl_check_view(get_class($this), 'login')): ?>
    <tr>
		<th><?php echo __('Login name') ?></th>
		<td><?php echo $device->login ?></td>
	</tr>
        <?php endif ?>
        <?php if ($this->acl_check_view(get_class($this), 'password')): ?>
	<tr>
		<th><?php echo __('Password') ?></th>
		<td><?php echo $device->password ?></td>
	</tr>
	<?php endif ?>
	<?php if ($device->price): ?>
	<tr> 
		<th><?php echo __('Price') ?></th>
		<td><?php echo $device->price ?></td>
	</tr>
	<tr>	
		<th><?php echo __('Monthly payment rate') ?></th>
		<td><?php echo $device->payment_rate ?></td>				    
	</tr>
	<?php endif; ?>
	<?php if ($device->buy_date != '' && $device->buy_date != '1970-01-01'): ?>
	<tr>
		<th><?php echo __('Buy date') ?></th>
		<td><?php echo $device->buy_date ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo __('Location address') ?></th>
		<td>
			<a href="<?php echo url_lang::base() ?>address_points/show/<?php echo $device->address_point->id ?>"><?php echo $device->address_point; ?></a>
		</td>
	</tr>
	<?php if ($gps != ''): ?>
	<tr>
		<th><?php echo __('GPS') ?></th>
		<td><?php echo $gps ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Comment') ?></th>
		<td><?php echo $device->comment ?></td>
	</tr>
</table>

<?php if (!empty($gps)): ?>
<a href="http://maps.google.com/maps?f=q&hl=<?php echo $lang ?>&geocode=&q=<?php echo $gpsx ?>,<?php echo $gpsy ?>&z=18&t=h&ie=UTF8" target="_blank">
	<img alt="<?php echo __('Address point detail') ?>" src="http://maps.google.com/maps/api/staticmap?center=<?php echo $gpsx ?>,<?php echo $gpsy ?>&zoom=18&maptype=hybrid&size=300x300&markers=color:red%7C<?php echo $gpsx ?>,<?php echo $gpsy ?>&sensor=false" style="float: right; margin-right: 10px;" />
</a>
<?php endif; ?>

<br class="clear" />
<br />

<br />

<div id="tabs">
	<ul class="tabs" style="font-size: 12px;">
		<li class="ui-corner-all"><a href="#interfaces"><?php echo __('Interfaces') ?></a></li>
		<?php if ($internal_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#internal_interfaces"><?php echo __('Internal interfaces') ?></a></li>
		<?php endif ?>
		<?php if ($ethernet_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#ethernet_interfaces"><?php echo __('Ethernet interfaces') ?></a></li>
		<?php endif ?>
		<?php if ($wireless_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#wireless_interfaces"><?php echo __('Wireless interfaces') ?></a></li>
		<?php endif ?>
		<?php if ($vlan_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#vlan_interfaces"><?php echo __('Vlan interfaces') ?></a></li>
		<?php endif ?>
		<?php if ($special_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#special_interfaces"><?php echo __('Special interfaces') ?></a></li>
		<?php endif ?>
		<?php if ($bridge_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#bridges"><?php echo __('Bridges') ?></a></li>
		<?php endif ?>
		<?php if ($port_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#ports"><?php echo __('Ports') ?></a></li>
		<?php endif ?>
	</ul>

<!-- interfaces -->
<?php if ($this->acl_check_view(get_class($this), 'iface', $device->user->member_id)) { ?>
	<div id="interfaces">
	<?php echo $ifaces ?>
	</div>
<?php } ?>

<!-- internal interfaces -->
<?php if ($this->acl_check_view(get_class($this), 'iface', $device->user->member_id)) { ?>
	<div id="internal_interfaces">
	<?php echo $internal_ifaces ?>
	</div>
<?php } ?>

<!-- ethernet interfaces -->
<?php if ($this->acl_check_view(get_class($this), 'iface', $device->user->member_id)) { ?>
	<div id="ethernet_interfaces">
	<?php echo $ethernet_ifaces ?>
	</div>
<?php } ?>

<!-- wireless interfaces -->
<?php if ($this->acl_check_view(get_class($this), 'iface', $device->user->member_id)) { ?>
	<div id="wireless_interfaces">
	<?php echo $wireless_ifaces ?>
	</div>
<?php } ?>

<!-- vlan interfaces -->
<?php if ($this->acl_check_view(get_class($this), 'iface', $device->user->member_id)) { ?>
	<div id="vlan_interfaces">
	<?php echo $vlan_ifaces ?>
	</div>
<?php } ?>

<!-- bridge interfaces -->
<?php if ($this->acl_check_view(get_class($this), 'iface', $device->user->member_id)) { ?>
	<div id="bridge_interfaces">
	<?php echo $bridge_ifaces ?>
	</div>
<?php } ?>

<!-- ports -->
<?php if ($this->acl_check_view(get_class($this), 'port', $device->user->member_id)) { ?>
	<div id="ports">
	<?php echo $port_ifaces ?>
	</div>
<?php } ?>

</div>

<br />

<!-- ip addresses -->
<?php if ($this->acl_check_view(get_class($this), 'ip_address', $device->user->member_id)) { ?>
<h3><a id="device_ip_addresses_link" name="ip_addresses"><?php echo __('IP addresses') ?> 
<img src="<?php echo url::base() ?>media/images/icons/ico_<?php echo (!$table_ip_addresses) ? 'add' : 'minus' ?>.gif" id="device_ip_addresses_button"></a></h3>
<div id="device_ip_addresses" class="<?php echo (!$table_ip_addresses) ? 'dispNone' : '' ?>">
<?php echo $table_ip_addresses ?><br />
</div><br />
<?php } ?>

<br class="clear" />
<br />

<!-- device engineers -->
<div style="float:left; width: 50%">
<?php //if ($this->acl_check_view(get_class($this),'engineer',$device->user->member_id)) { ?>
<h3><a id="device_engineers_link" name="engineers"><?php echo __('Device engineers') ?> 
<img src="<?php echo url::base() ?>media/images/icons/ico_<?php echo ($count_engineers == 0) ? 'add' : 'minus' ?>.gif" id="device_engineers_button"></a>
<?php echo help::hint('engineers') ?></h3>
<div id="device_engineers" class="<?php echo ($count_engineers == 0) ? 'dispNone' : '' ?>">
<?php echo $table_device_engineers ?>
</div>
<?php //} ?>
</div>

<!-- device admins -->
<div style="float:left; margin-left:0px;">
<?php if ($this->acl_check_view(get_class($this), 'admin', $device->user->member_id)) { ?>
<h3><a id="device_admins_link" name="admins"><?php echo __('Device admins') ?> 
<img src="<?php echo url::base() ?>media/images/icons/ico_<?php echo ($count_admins == 0) ? 'add' : 'minus' ?>.gif" id="device_admins_button"></a>
<?php echo help::hint('admins') ?></h3>
<div id="device_admins" class="<?php echo ($count_admins == 0) ? 'dispNone' : '' ?>">
<?php echo $table_device_admins ?>
</div>
<?php } ?>
</div>
