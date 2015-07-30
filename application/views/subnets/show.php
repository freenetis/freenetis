<h2><?php echo $headline ?></h2>
<br />
<?php
if (isset($submenu))
{
	echo '<div class="submenu">' . $submenu . '</div>';
}

$links = array();

if ($this->acl_check_edit('Subnets_Controller', 'subnet'))
{
	$links[] = html::anchor('subnets/edit/' . $subnet->id, __('Edit'));
}

if ($this->acl_check_delete('Subnets_Controller', 'subnet'))
{
	$links[] = html::anchor('subnets/delete/' . $subnet->id, __('Delete'));
}

if (module::e('notification') &&
	$this->acl_check_new('Notifications_Controller', 'subnet'))
{
	$links[] = html::anchor('notifications/subnet/' . $subnet->id, __('Notifications'));
}

if ($this->acl_check_view('Subnets_Controller', 'subnet'))
{
	$links[] = html::anchor(
		'export/csv/subnets/null/' . $subnet->id,
		__('Export to CSV'),
		array
		(
			'class' => 'popup_link'
		)
	);
}

echo implode(' | ', $links);

?>
<br /><br />
<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $subnet->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Subnet name') ?></th>
		<td><?php echo $subnet->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Network address') ?></th>
		<td><?php echo $subnet->network_address ?></td>
	</tr>
	<tr>
		<th><?php echo __('Netmask') ?></th>
		<td><?php echo $subnet->netmask ?></td>
	</tr>
	<?php if ($owner_id): ?>
		<tr>
			<th><?php echo __('Owner') ?> <?php echo help::hint('subnet_owner') ?></th>
			<td><?php echo html::anchor('members/show/' . $owner_id, $owner) ?></td>
		</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('OSPF area ID') ?></th>
		<td><?php echo $subnet->OSPF_area_id ?></td>
	</tr>
	<?php if ($this->acl_check_view('Subnets_Controller', 'redirect')): ?>
	<tr>
		<th><?php echo __('Redirection enabled') ?></th>
		<td><?php echo ($subnet->redirect == 1) ? __('Yes') : __('No') ?></td>
	</tr>
	<?php endif ?>
	<?php if ($this->acl_check_view('Subnets_Controller', 'dhcp')): ?>
	<tr>
		<th><?php echo __('DHCP server') ?> <?php echo help::hint('subnet_dhcp') ?></th>
		<td><?php echo ($subnet->dhcp == 1) ? __('Yes') : __('No') ?></td>
	</tr>
	<?php endif ?>
	<?php if ($this->acl_check_view('Subnets_Controller', 'dns')): ?>
	<tr>
		<th><?php echo __('DNS server') ?> <?php echo help::hint('subnet_dns') ?></th>
		<td><?php echo ($subnet->dns == 1) ? __('Yes') : __('No') ?></td>
	</tr>
	<?php endif ?>
	<?php if ($this->acl_check_view('Subnets_Controller', 'qos')): ?>
	<tr>
		<th><?php echo __('QoS') ?> <?php echo help::hint('subnet_qos') ?></th>
		<td><?php echo ($subnet->qos == 1) ? __('Yes') : __('No') ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Cloud') ?></th>
		<td>
		<?php
			$clouds_links = array();
			foreach ($clouds as $cloud)
			{
				$clouds_links[] = html::anchor('clouds/show/' . $cloud->id, $cloud->name);
			}
			echo implode(', ', $clouds_links);
		?>
		</td>
	</tr>
	<tr>
		<th><?php echo __('Used') ?> <?php echo help::hint('subnet_used_ips') ?></th>
		<td><?php
			$color = '';

			if ($used > 80)
				$color = 'red';

			if ($used < 20)
				$color = 'green';

			echo ($color != '') ? "<span style='color: $color'>$used %</span>" : "$used %";
			
	?>   (<?php echo $total_used ?> <?php echo __('from2') ?> <?php echo $total_available ?>)</td>
	</tr>
</table>
<br /><br />
<?php echo $grid ?>
				


