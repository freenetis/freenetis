<h2><?php echo $headline ?></h2>
<br />
<?php
if (isset($submenu))
{
	echo '<div class="submenu">' . $submenu . '</div>';
}

$links = array();

if ($this->acl_check_edit('Devices_Controller', 'subnet'))
{
	$links[] = html::anchor('subnets/edit/' . $subnet->id, __('Edit'));
}

if ($this->acl_check_delete('Devices_Controller', 'subnet'))
{
	$links[] = html::anchor('subnets/delete/' . $subnet->id, __('Delete'));
}

if ($this->acl_check_edit('Devices_Controller', 'redirect'))
{
	$links[] = html::anchor('notifications/subnet/' . $subnet->id, __('Notifications'));
}

if ($this->acl_check_view('Devices_Controller', 'subnet'))
{
	$links[] = html::anchor('export/csv/subnets/utf-8/' . $subnet->id, __('Export to CSV (utf-8)'));
	$links[] = html::anchor('export/csv/subnets/windows-1250/' . $subnet->id, __('Export to CSV (windows-1250)'));
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
	<tr>
		<th><?php echo __('Redirection enabled') ?></th>
		<td><?php echo ($subnet->redirect == 1) ? __('Yes') : __('No') ?></td>
	</tr>
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
				


