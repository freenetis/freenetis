<h2><?php echo  $headline ?></h2>
<?php if (isset($submenu)) echo '<div class="submenu">'.$submenu.'</div>'; ?>
<?php 
$links = array();

if (!$ip_address->member_id && $this->acl_check_edit('Ip_addresses_Controller', 'ip_address'))
	$links[] = html::anchor('ip_addresses/edit/'.$ip_address->id, __('Edit'), array('class' => 'popup_link'));
if (!$ip_address->member_id && $this->acl_check_delete('Ip_addresses_Controller', 'ip_address'))
	$links[] = html::anchor('ip_addresses/delete/'.$ip_address->id, __('Delete'));
if (module::e('redirection') && $this->acl_check_new('Redirect_Controller', 'redirect'))
	$links[] = html::anchor('redirect/activate_to_ip_address/'.$ip_address->id, __('Activate redirection'));
if (module::e('redirection') && $this->acl_check_view('Redirect_Controller', 'redirect'))
	$links[] = html::anchor(url::base().'redirection/?ip_address='.$ip_address->ip_address, __('Redirection preview'));

echo implode(' | ', $links);
?>
<br /><br />
<table class="extended" cellspacing="0" style="float:left;">
	<tr>
		<th><?php echo  __('ID') ?></th>
		<td><?php echo  $ip_address->id ?></td>
	</tr>
	<tr>
		<th><?php echo  __('IP address') ?></th>
		<td><?php echo  $ip_address->ip_address ?></td>
	</tr>
	<?php if (!$ip_address->member_id): ?>
	<tr>
		<th><?php echo  __('Member') ?></th>
		<td><?php echo  html::anchor('members/show/'.$member->id, $member->name) ?></td>
	</tr>
	<?php else: ?>
	<tr>
		<th><?php echo  __('Member') ?></th>
		<td><?php echo  html::anchor('members/show/'.$ip_address->member_id, $ip_address->member->name) ?></td>
	</tr>
	<?php endif ?>
	<?php if (!$ip_address->member_id): ?>
	<tr>
		<th><?php echo  __('Device') ?></th>
		<td><?php echo  html::anchor('devices/show/'.$device->id, $device->name) ?></td>
	</tr>
	<?php if (url_lang::current(TRUE) == 'devices'): ?>
	<tr>
		<th><?php echo  __('Interface') ?></th>
		<td><?php echo  html::anchor('devices/show_iface/'.$iface->id, $iface_name) ?></td>
	</tr>
	<?php else: ?>
	<tr>
		<th><?php echo  __('Interface') ?></th>
		<td><?php echo  html::anchor('ifaces/show/'.$iface->id, $iface_name) ?></td>
	</tr>
	<?php endif ?>
	<?php endif ?>
</table>
<table class="extended" cellspacing="0" style="float:left; margin-left:10px;">
	<tr>
		<th><?php echo  __('Subnet name') ?></th>
		<?php if ($this->acl_check_view('Subnets_Controller', 'subnet')): ?>
		<td><?php echo  html::anchor('subnets/show/'.$ip_address->subnet_id, $ip_address->subnet->name) ?></td>
		<?php else: ?>
		<td><?php echo  $ip_address->subnet->name ?></td>
		<?php endif ?>
	</tr>
	<tr>
		<th><?php echo  __('Subnet network address') ?></th>
		<td><?php echo  $ip_address->subnet->network_address ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Subnet netmask') ?></th>
		<td><?php echo  $ip_address->subnet->netmask ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Gateway').'&nbsp;'.help::hint('gateway') ?></th>
		<td><?php echo  $ip_address->gateway ? __('Yes') : __('No') ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Service').'&nbsp;'.help::hint('service') ?></th>
		<td><?php echo  $ip_address->service ? __('Yes') : __('No') ?></td>
	</tr>
					
					
</table>
<br class="clear"/>
<br />
<?php echo $grid ?>
