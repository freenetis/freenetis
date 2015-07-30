<?php if(count($device_ifaces[Iface_Model::TYPE_WIRELESS])): ?>
/interface wireless
<?php foreach ($device_ifaces[Iface_Model::TYPE_WIRELESS] as $iface_id => $iface)
{
	if ($iface->wireless_norm)
	{
		$norm = $iface->wireless_norm;
		if ($iface->wireless_frequency !="")
			$frequency = " frequency=".$iface->wireless_frequency;
	}
	else
	{
		$norm = Link_Model::NORM_802_11_A;
		$frequency = "";
	}
?>
set <?php echo $iface_id ?> arp=enabled disabled=no mac-address=<?php echo $iface->mac ?> name="<?php echo $iface->name ?>" <?php
switch ($iface->wireless_mode)
{
	case Iface_Model::WIRELESS_MODE_AP:
		echo "mode=ap-bridge ";
		break;
	case Iface_Model::WIRELESS_MODE_CLIENT:
		echo "mode=station ";
		break;
}
switch ($norm)
{
	case Link_Model::NORM_802_11_B:
		echo " band=2ghz-b ";
		break;
	case Link_Model::NORM_802_11_G:
		echo " band=2ghz-onlyg ";
		break;
	case Link_Model::NORM_802_11_B_G:
		echo " band=2ghz-b/g ";
		break;
	case Link_Model::NORM_802_11_A:
	case Link_Model::NORM_802_11_N:
		echo " band=5ghz-a ";
		break;
}
if ($iface->wireless_channel_width != "")
	echo "channel-width=".$iface->wireless_channel_width."mhz ";
?>country="czech republic" default-authentication=yes<?php echo $frequency ?> ssid="<?php echo $iface->wireless_ssid ?>"
<?php } ?>
<?php foreach ($device_ifaces[Iface_Model::TYPE_VIRTUAL_AP] as $iface): ?>
add arp=enabled disabled=no mac-address=<?php echo $iface->mac ?> master-interface="<?php echo $iface->parent_name ?>" name="<?php echo $iface->name ?>" ssid="<?php echo $iface->wireless_ssid ?>"
<?php endforeach ?>
/interface wireless access-list
<?php foreach ($device_ifaces[Iface_Model::TYPE_WIRELESS] as $iface_id => $iface): ?>
<?php foreach ($device_wireless_iface_devices[$iface->id] as $device_wireless_iface_device): ?>
add disabled=no interface="<?php echo $iface->name ?>" forwarding=no mac-address=<?php echo $device_wireless_iface_device->mac ?> comment="<?php echo text::cs_utf2ascii(text::object_format($device_wireless_iface_device, "ID {member_id} - {member_name} - {name} (IP {ip_address})")) ?>" 
<?php endforeach ?>
<?php endforeach ?>
<?php endif ?>
/interface ethernet
<?php foreach ($device_ifaces[Iface_Model::TYPE_ETHERNET] as $iface_id => $iface): ?>
set <?php echo $iface_id ?> arp=enabled auto-negotiation=yes disabled=no full-duplex=yes mac-address=<?php echo $iface->mac ?> name="<?php echo $iface->name ?>"
<?php endforeach ?>
/interface vlan
<?php foreach ($device_ifaces[Iface_Model::TYPE_VLAN] as $iface_id => $iface): ?>
add arp=enabled disabled=no interface="<?php echo $iface->parent_name ?>" name="<?php echo $iface->name ?>" vlan-id=<?php echo $iface->tag_802_1q ?> 
<?php endforeach ?>
/ip address
<?php foreach ($device_ip_addresses as $device_ip_address): ?>
add address=<?php echo $device_ip_address->ip_address ?>/<?php echo $device_ip_address->subnet_range ?> disabled=no interface="<?php echo $device_ip_address->iface_name !='' ? $device_ip_address->iface_name : $device_ip_address->vlan_iface_name ?>" network=<?php echo $device_ip_address->subnet_network ?> 
<?php endforeach ?>
/ip dns
set allow-remote-requests=yes servers=<?php echo $dns_servers ?> 
/ip pool
<?php foreach ($dhcp_subnets as $dhcp_subnet): ?>
add name="<?php echo $dhcp_subnet->iface ?>" ranges=<?php echo $dhcp_subnet->subnet_range_start ?>-<?php echo $dhcp_subnet->subnet_range_end ?> 
<?php endforeach ?>
/ip dhcp-server
<?php foreach ($dhcp_subnets as $dhcp_subnet): ?>
add address-pool="<?php $dhcp_subnet->iface ?>" authoritative=after-2sec-delay bootp-support=static disabled=no interface="<?php echo $dhcp_subnet->iface ?>" lease-time=3d name="<?php echo $dhcp_subnet->iface ?>"
<?php endforeach ?>
/ip dhcp-server config
set store-leases-disk=5m
/ip dhcp-server network
<?php foreach ($dhcp_subnets as $dhcp_subnet): ?>
add address=<?php echo $dhcp_subnet->network_address ?>/<?php echo $dhcp_subnet->subnet_range ?> dhcp-option="" dns-server="" gateway=<?php echo $dhcp_subnet->ip_address ?> ntp-server="" wins-server=""
<?php endforeach ?>
/ip dhcp-server lease
<?php foreach ($dhcp_subnets as $subnet_id => $dhcp_subnet): ?>
<?php foreach ($dhcp_ip_addresses[$subnet_id] as $dhcp_ip_address): ?>
add address=<?php echo $dhcp_ip_address->ip_address ?> disabled=no mac-address=<?php echo $dhcp_ip_address->mac ?> server="<?php echo $dhcp_subnet->iface !='' ? $dhcp_subnet->iface : $dhcp_subnet->vlan_iface ?>" comment="ID <?php echo $dhcp_ip_address->member_id ?> - <?php echo text::cs_utf2ascii($dhcp_ip_address->member_name) ?> - <?php echo text::cs_utf2ascii($dhcp_ip_address->device_name) ?>"
<?php endforeach ?>
<?php endforeach ?>
/ip route
<?php foreach ($device_gateways as $device_gateway): ?>
add disabled=no dst-address=0.0.0.0/0 gateway=<?php echo $device_gateway ?> 
<?php endforeach ?>
/queue simple
add disabled=no target-addresses=0.0.0.0/0 interface=all name="Parent queue" parent=none priority=1 
<?php foreach ($qos_subnets as $qos_subnet): ?>
add disabled=no target-addresses=<?php echo $qos_subnet->network_address ?>/<?php echo $qos_subnet->subnet_range ?> interface="<?php echo $qos_subnet->iface !='' ? $qos_subnet->iface : $qos_subnet->vlan_iface ?>" name="<?php echo text::cs_utf2ascii($qos_subnet->name) ?>" priority=2 parent="Parent queue"
<?php endforeach ?>
<?php foreach ($qos_subnets as $subnet_id => $qos_subnet)
{
	$names[] = array();
	foreach ($qos_ip_addresses[$subnet_id] as $member_id => $qos_member_ip_addresses)
	{
		if ($member_id != 1)
			$qos_name = "ID $member_id - ".text::cs_utf2ascii($device_members[$member_id]);
		else
			$qos_name = "ID $member_id - ".text::cs_utf2ascii($qos_subnet->name);
		
		if (in_array($qos_name, $names))
			$qos_name .= " (".text::cs_utf2ascii($qos_subnet->name).")";
		
		$names[] = $qos_name;
		
		?>add disabled=no interface=all target-addresses=<?php echo implode(",",$qos_member_ip_addresses) ?> name="<?php echo $qos_name ?>" parent="<?php echo text::cs_utf2ascii($qos_subnet->name) ?>" priority=8
<?php
	}
} ?>
<?php foreach ($qos_subnets as $qos_subnet): ?>
add disabled=no target-addresses=<?php echo $qos_subnet->network_address ?>/<?php echo $qos_subnet->subnet_range ?> interface="<?php echo $qos_subnet->iface !='' ? $qos_subnet->iface : $qos_subnet->vlan_iface ?>" name="others on <?php echo text::cs_utf2ascii($qos_subnet->name) ?>" parent="<?php echo text::cs_utf2ascii($qos_subnet->name) ?>" priority=8
<?php endforeach ?>
/system identify set name="<?php echo text::cs_utf2ascii($name) ?>"
<?php if (isset($device_gateway)): ?>
/system watchdog set watchdog-timer=yes watch-address=<?php echo $device_gateway ?> 
<?php endif ?>