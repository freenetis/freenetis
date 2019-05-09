<h2><?php echo __('Device') ?> <?php echo $device->name ?></h2>
<br />
<?php

$links = array();

if ($this->acl_check_edit('Devices_Controller', 'devices', $device->user->member_id)) 
	$links[] = html::anchor('devices/edit/'.$device->id, __('Edit'));

if ($this->acl_check_delete('Devices_Controller', 'devices', $device->user->member_id))
	$links[] = html::anchor('devices/delete/'.$device->id, __('Delete'), array('class' => 'delete_link'));

if (Settings::get('syslog_ng_mysql_api_enabled') &&
	$this->acl_check_view('Device_logs_Controller', 'device_log', $device->user->member_id))
{
	$links[] = html::anchor('device_logs/show_by_device/'.$device->id, __('Show logs'));
}

if (module::e('notification') &&
	$this->acl_check_new('Notifications_Controller', 'device'))
{
	$links[] = html::anchor(
			'notifications/device/'.$device->id, __('Notifications'),
			array('title' => __('Set notification to device admins'))
	);
}

if ($this->acl_check_view('Devices_Controller', 'export', $device->user->member_id))
	$links[] = html::anchor('devices/export/'.$device->id, __('Export'));

if ($this->acl_check_view('Devices_Controller', 'map', $device->user->member_id))
	$links[] = html::anchor('devices/map/'.$device->id, __('Show subdevices tree'));

$links[] = html::anchor('devices/topology/'.$device->id, __('Show topology'));

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

<div style="display: grid; grid-column-gap: 50px; grid-template-columns: 400px auto;">

<table class="extended" cellspacing="0" style="word-wrap: break-word;">
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
	<?php if ($this->acl_check_view('Devices_Controller', 'login')): ?>
    <tr>
		<th><?php echo __('Login name') ?></th>
		<td><?php echo $device->login ?></td>
	</tr>
        <?php endif ?>
        <?php if ($this->acl_check_view('Devices_Controller', 'password')): ?>
	<tr>
		<th><?php echo __('Password') ?></th>
		<td><?php echo $device->password ?></td>
	</tr>
	<?php endif ?>
	<?php if (Settings::get('finance_enabled') && $device->price): ?>
	<tr> 
		<th><?php echo __('Price') ?></th>
		<td><?php echo money::format($device->price) ?></td>
	</tr>
	<tr>	
		<th><?php echo __('Monthly payment rate') ?></th>
		<td><?php echo money::format($device->payment_rate) ?></td>				    
	</tr>
	<?php endif; ?>
	<?php if (Settings::get('finance_enabled') && $device->buy_date != '' && $device->buy_date != '1970-01-01'): ?>
	<tr>
		<th><?php echo __('Buy date') ?></th>
		<td><?php echo $device->buy_date ?></td>
	</tr>
	<?php endif; ?>
	<?php if ($device->access_time): ?>
	<tr>
		<th><?php echo __('Last access time') ?></th>
		<td><?php echo $device->access_time ?> (<?php echo callback::datetime_diff($device, 'access_time') ?>)</td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Location address') ?></th>
		<td>
			<?php if ($this->acl_check_view('Address_points_Controller', 'address_point')): ?>
			<a href="<?php echo url_lang::base() ?>address_points/show/<?php echo $device->address_point->id ?>"><?php echo $device->address_point; ?></a>
			<?php else: ?>
			<?php echo $device->address_point; ?>
			<?php endif ?>
		</td>
	</tr>
	<?php if ($gps != ''): ?>
	<tr>
		<th><?php echo __('GPS') ?></th>
		<td><?php echo $gps ?></td>
	</tr>
	<?php endif ?>
	<?php if ($device->connection_requests->count()): $cr_model = $device->connection_requests->current(); ?>
	<tr>
		<th><?php echo __('Created from connection request') ?></th>
		<td><?php echo html::anchor('connection_requests/show/' . $cr_model->id, $cr_model->id . ' (' . $cr_model->created_at . ')') ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Comment') ?></th>
		<td><?php echo $device->comment ?></td>
	</tr>
	<?php if ($this->acl_check_view('Device_active_links_Controller', 'display_device_active_links') && $active_links):?>
	<tr>
	<th><?php echo __('Device active links') ?></th>
		<td>
		<?php callback::device_active_links($device, 'device_show') ?>
		</td>
	</tr>
	<?php endif; ?>
</table>

<?php if (!empty($gps)): ?>
<div id="ap_gmap">
</div>
<script type="text/javascript">
	$(document).ready(function () {
		mapycz_dev('ap_gmap', <?php echo $gpsx ?>, <?php echo $gpsy ?>);
	});
</script>
<?php endif; ?>

</div>

<br />
<br />

<?php if ($this->acl_check_view('Ifaces_Controller', 'iface', $device->user->member_id)): ?>

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
			<li class="ui-corner-all"><a href="#bridge_interfaces"><?php echo __('Bridge interfaces') ?></a></li>
		<?php endif ?>
		<?php if ($port_ifaces != ''): ?>
			<li class="ui-corner-all"><a href="#ports"><?php echo __('Ports') ?></a></li>
		<?php endif ?>
	</ul>

<!-- interfaces -->
	<div id="interfaces">
	<?php echo $ifaces ?>
	</div>

<!-- internal interfaces -->
	<div id="internal_interfaces">
	<?php echo $internal_ifaces ?>
	</div>

<!-- ethernet interfaces -->
	<div id="ethernet_interfaces">
	<?php echo $ethernet_ifaces ?>
	</div>

<!-- wireless interfaces -->
	<div id="wireless_interfaces">
	<?php echo $wireless_ifaces ?>
	</div>

<!-- vlan interfaces -->
	<div id="vlan_interfaces">
	<?php echo $vlan_ifaces ?>
	</div>

<!-- bridge interfaces -->
	<div id="bridge_interfaces">
	<?php echo $bridge_ifaces ?>
	</div>

<!-- ports -->
	<div id="ports">
	<?php echo $port_ifaces ?>
	</div>
</div>

<br />

<?php endif ?>

<!-- ip addresses -->
<?php if ($this->acl_check_view('Ip_addresses_Controller', 'ip_address', $device->user->member_id)) { ?>
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
<?php if ($this->acl_check_view('Devices_Controller', 'engineer',$device->user->member_id)) { ?>
<h3><a id="device_engineers_link" name="engineers"><?php echo __('Device engineers') ?> 
<img src="<?php echo url::base() ?>media/images/icons/ico_<?php echo ($count_engineers == 0) ? 'add' : 'minus' ?>.gif" id="device_engineers_button"></a>
<?php echo help::hint('engineers') ?></h3>
<div id="device_engineers" class="<?php echo ($count_engineers == 0) ? 'dispNone' : '' ?>">
<?php echo $table_device_engineers ?>
</div>
<?php } ?>
</div>

<!-- device admins -->
<div style="float:left; margin-left:0px;">
<?php if ($this->acl_check_view('Devices_Controller', 'admin', $device->user->member_id)) { ?>
<h3><a id="device_admins_link" name="admins"><?php echo __('Device admins') ?> 
<img src="<?php echo url::base() ?>media/images/icons/ico_<?php echo ($count_admins == 0) ? 'add' : 'minus' ?>.gif" id="device_admins_button"></a>
<?php echo help::hint('admins') ?></h3>
<div id="device_admins" class="<?php echo ($count_admins == 0) ? 'dispNone' : '' ?>">
<?php echo $table_device_admins ?>
</div>
<?php } ?>
</div>
