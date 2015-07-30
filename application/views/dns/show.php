<h2><?php echo $zone->zone ?></h2>

<br />
<?php
$links = array();
if ($this->acl_check_edit('Dns_Controller', 'zone'))
{
	$links[] = html::anchor('dns/edit/' . $zone->id, __('Edit'));
}
if ($this->acl_check_delete('Dns_Controller', 'zone'))
{
	$links[] = html::anchor('dns/delete/' . $zone->id, __('Delete'), array('class' => 'delete_link'));
}
echo implode(' | ', $links)
?>

<br /><br />

<table class="extended" style="float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Basic information') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Zone ID')?></th>
		<td><?php echo $zone->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone')?></th>
		<td><a href='http://<?php echo $zone->zone?>'><?php echo $zone->zone?></a></td>
	</tr>
	<tr>
		<th><?php echo __('Primary server')?></th>
		<td>
		<?php
		if ($this->acl_check_view('Ip_addresses_Controller', 'ip_address', $zone->ip_address->iface->device->user->member->id))
		{
			echo html::anchor('ip_addresses/show/'.$zone->ip_address_id, $zone->ip_address);
		}
		else
		{
			echo $zone->ip_address;
		}?></a></td>
	</tr>
	<?php if ($secondary_servers->count() > 0): ?>
	<tr>
		<th><?php echo __('Secondary servers')?></th>
		<td>
		<?php
		foreach ($secondary_servers as $ip)
		{
			if ($this->acl_check_view('Ip_addresses_Controller', 'ip_address', $ip->ip_address->iface->device->user->member->id))
			{
				echo html::anchor('ip_addresses/show/'.$ip->ip_address_id, $ip->ip_address);
			}
			else
			{
				echo $ip->ip_address;
			}
		}?></a></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th colspan="2"><?php echo  __('SOA record') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Primary name server')?></th>
		<td><?php echo $zone->nameserver ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone administrator E-mail')?></th>
		<td><?php echo $zone->email ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone serial number')?></th>
		<td><?php echo $zone->sn ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone refresh time')?></th>
		<td><?php echo $zone->refresh ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone retry update time')?></th>
		<td><?php echo $zone->retry ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone expire time')?></th>
		<td><?php echo $zone->expire ?></td>
	</tr>
	<tr>
		<th><?php echo __('Zone not exists time')?></th>
		<td><?php echo $zone->nx ?></td>
	</tr>
</table>

<br class="clear" /><br />

<?php if (count($a_records) > 0): ?>
<h3><?php echo __('A records') ?></h3>
<table  class="extended" style="float:left; width:500px;">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('TTL') ?></th>
		<th><?php echo __('Value') ?></th>
		<th><?php echo __('Reverse record') ?></th>
	</tr>
	<?php foreach ($a_records as $r): ?>
	
	<tr>
		<td><?php echo $r->name ?></td>
		<td><?php echo $r->ttl ?></td>
		<td><?php echo $r->value ?></td>
		<td><?php echo ($r->param == 'on' ? __('Yes') : __('No')) ?></td>
	</tr>
	<?php endforeach; ?>
</table>
<br class="clear" /><br />
<?php endif; ?>

<?php if (count($aaaa_records) > 0): ?>
<h3><?php echo __('AAAA records') ?></h3>
<table  class="extended" style="float:left; width:500px;">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('TTL') ?></th>
		<th><?php echo __('Value') ?></th>
		<th><?php echo __('Reverse record') ?></th>
	</tr>
	<?php foreach ($aaaa_records as $r): ?>
	
	<tr>
		<td><?php echo $r->name ?></td>
		<td><?php echo $r->ttl ?></td>
		<td><?php echo $r->value ?></td>
		<td><?php echo ($r->param == 'on' ? __('Yes') : __('No')) ?></td>
	</tr>
	<?php endforeach; ?>
</table>
<br class="clear" /><br />
<?php endif; ?>

<?php if (count($cname_records) > 0): ?>
<h3><?php echo __('CNAME records') ?></h3>
<table  class="extended" style="float:left; width:500px;">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('TTL') ?></th>
		<th><?php echo __('Value') ?></th>
	</tr>
	<?php foreach ($cname_records as $r): ?>
	
	<tr>
		<td><?php echo $r->name  ?></td>
		<td><?php echo $r->ttl?></td>
		<td><?php echo $r->value  ?></td>
	</tr>
	<?php endforeach; ?>
</table>
<br class="clear" /><br />
<?php endif; ?>

<?php if (count($ns_records) > 0): ?>
<h3><?php echo __('NS records') ?></h3>
<table  class="extended" style="float:left; width:500px;">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('TTL') ?></th>
		<th><?php echo __('Value') ?></th>
	</tr>
	<?php foreach ($ns_records as $r): ?>
	
	<tr>
		<td><?php echo $r->name ?></td>
		<td><?php echo $r->ttl ?></td>
		<td><?php echo $r->value  ?></td>
	</tr>
	<?php endforeach; ?>
</table>
<br class="clear" /><br />
<?php endif; ?>


<?php if (count($mx_records) > 0): ?>
<h3><?php echo __('MX records') ?></h3>
<table  class="extended" style="float:left; width:500px;">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('TTL') ?></th>
		<th><?php echo __('Value') ?></th>
		<th><?php echo __('Priority') ?></th>
	</tr>
	<?php foreach ($mx_records as $r): ?>
	
	<tr>
		<td><?php echo $r->name ?></td>
		<td><?php echo $r->ttl ?></td>
		<td><?php echo $r->value  ?></td>
		<td><?php echo $r->param ?></td>
	</tr>
	<?php endforeach; ?>
</table>
<br class="clear" /><br />
<?php endif; ?>
