/ip pool
remove [find]
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
add name="<?php echo text::cs_utf2ascii($dhcp_server->name) ?>" ranges=<?php echo $dhcp_server->range_start ?>-<?php echo $dhcp_server->range_end ?> 
<?php endforeach ?>
/ip dhcp-server
remove [find]
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
add name="<?php echo text::cs_utf2ascii($dhcp_server->name) ?>" address-pool="<?php echo text::cs_utf2ascii($dhcp_server->name) ?>" authoritative=after-2sec-delay bootp-support=static disabled=no interface="<?php echo $dhcp_server->interface ?>" lease-time=3h
<?php endforeach ?>
/ip dhcp-server network
remove [find]
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
add address=<?php echo $dhcp_server->cidr ?> dhcp-option="" dns-server=<?php echo implode(',', $dhcp_server->dns_servers) ?> gateway=<?php echo $dhcp_server->gateway ?> ntp-server="" wins-server="" comment="<?php echo text::cs_utf2ascii(text::object_format($dhcp_server, $dhcp_server->comment)) ?>"
<?php endforeach ?>
/ip dhcp-server lease
remove [find]
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
<?php foreach ($dhcp_server->hosts as $host): ?>
add address=<?php echo $host->ip_address ?> disabled=no mac-address=<?php echo $host->mac ?> server="<?php echo $host->server ?>" comment="<?php echo text::cs_utf2ascii(text::object_format($host, $host->comment)) ?>"
<?php endforeach ?>
<?php endforeach ?>