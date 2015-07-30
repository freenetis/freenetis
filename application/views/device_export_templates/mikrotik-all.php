<?php
if (isset($result->interfaces[Iface_Model::TYPE_WIRELESS]))
{
	?>
/interface wireless
<?php
foreach ($result->interfaces[Iface_Model::TYPE_WIRELESS] as $id => $iface)
{
	if ($iface->wireless->norm)
	{
		$norm = $iface->wireless->norm;
		if ($iface->wireless->frequency !="")
			$frequency = " frequency=".$iface->wireless->frequency;
	}
	else
	{
		$norm = Link_Model::NORM_802_11_A;
		$frequency = "";
	}
?>
set <?php echo $id ?> arp=enabled disabled=no mac-address=<?php echo $iface->mac ?> name="<?php echo $iface->name ?>" comment="<?php echo text::cs_utf2ascii(text::object_format($iface, $iface->comment)) ?>"  <?php
switch ($iface->wireless->mode)
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
if ($iface->wireless->channel_width != "")
	echo "channel-width=".$iface->wireless->channel_width."mhz ";
?>country="czech republic" default-authentication=yes<?php echo $frequency ?> ssid="<?php echo $iface->wireless->ssid ?>"
<?php } ?>
<?php if (isset($result->interfaces[Iface_Model::TYPE_VIRTUAL_AP])): ?>
<?php foreach ($result->interfaces[Iface_Model::TYPE_VIRTUAL_AP] as $iface): ?>
add arp=enabled disabled=no mac-address=<?php echo $iface->mac ?> master-interface="<?php echo $iface->parent_name ?>" name="<?php echo $iface->name ?>" ssid="<?php echo $iface->wireless_ssid ?>"
<?php endforeach ?>
<?php endif ?>
/interface wireless access-list
<?php
foreach ($result->interfaces[Iface_Model::TYPE_WIRELESS] as $id => $iface)
{
	foreach ($iface->wireless->clients as $client)
	{
?>
add disabled=no interface="<?php echo $iface->name ?>" forwarding=no mac-address=<?php echo $client->mac ?> comment="<?php echo text::cs_utf2ascii(text::object_format($client, $client->comment)) ?>" 
<?php	
	}
}
}
?>
/interface ethernet
<?php
if (isset($result->interfaces[Iface_Model::TYPE_ETHERNET]))
{
	foreach ($result->interfaces[Iface_Model::TYPE_ETHERNET] as $id => $iface)
	{
?>
set <?php echo $id ?> arp=enabled auto-negotiation=yes disabled=no full-duplex=yes mac-address=<?php echo $iface->mac ?> name="<?php echo $iface->name ?>" comment="<?php echo text::cs_utf2ascii(text::object_format($iface, $iface->comment)) ?>"
<?php
	}
}
?>
<?php if (isset($result->interfaces[Iface_Model::TYPE_VLAN])): ?>
/interface vlan
<?php foreach ($result->interfaces[Iface_Model::TYPE_VLAN] as $id => $iface): ?>
add arp=enabled disabled=no interface="<?php echo $iface->vlan->parent_interface ?>" name="<?php echo $iface->name ?>" vlan-id=<?php echo $iface->vlan->tag_802_1q ?>  comment="<?php echo text::cs_utf2ascii($iface->vlan->name) ?>"
<?php endforeach ?>
<?php endif ?>
/ip address
<?php foreach ($result->ip_addresses as $address): ?>
add address=<?php echo $address->address ?>/<?php echo network::netmask2cidr($address->netmask) ?> disabled=no interface="<?php echo $address->interface ?>" network=<?php echo $address->network ?> comment="<?php echo text::cs_utf2ascii(text::object_format($address, $address->comment)) ?>"
<?php endforeach ?>
/ip dns
set allow-remote-requests=yes servers=<?php echo implode(", ",$result->dns_servers) ?> 
/ip pool
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
add name="<?php echo text::cs_utf2ascii($dhcp_server->name) ?>" ranges=<?php echo $dhcp_server->range_start ?>-<?php echo $dhcp_server->range_end ?> 
<?php endforeach ?>
/ip dhcp-server
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
add name="<?php echo text::cs_utf2ascii($dhcp_server->name) ?>" address-pool="<?php echo text::cs_utf2ascii($dhcp_server->name) ?>" authoritative=after-2sec-delay bootp-support=static disabled=no interface="<?php echo $dhcp_server->interface ?>" lease-time=3d
<?php endforeach ?>
/ip dhcp-server config
set store-leases-disk=5m
/ip dhcp-server network
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
add address=<?php echo $dhcp_server->cidr ?> dhcp-option="" dns-server="<?php echo implode(", ",$dhcp_server->dns_servers) ?>" gateway=<?php echo $dhcp_server->gateway ?> ntp-server="" wins-server="" comment="<?php echo text::cs_utf2ascii(text::object_format($dhcp_server, $dhcp_server->comment)) ?>"
<?php endforeach ?>
/ip dhcp-server lease
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
<?php foreach ($dhcp_server->hosts as $host): ?>
add address=<?php echo $host->ip_address ?> disabled=no mac-address=<?php echo $host->mac ?> server="<?php echo $host->server ?>" comment="<?php echo text::cs_utf2ascii(text::object_format($host, $host->comment)) ?>"
<?php endforeach ?>
<?php endforeach ?>
/ip route
<?php foreach ($result->gateways as $gateway): ?>
add disabled=no dst-address=0.0.0.0/0 gateway=<?php echo $gateway ?> 
<?php endforeach ?>
/system identify set name="<?php echo text::cs_utf2ascii($result->name) ?>"
<?php if (isset($gateway)): ?>
/system watchdog set watchdog-timer=yes watch-address=<?php echo $gateway ?> 
<?php endif ?>