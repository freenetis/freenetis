/ip dhcp-server lease
<?php foreach ($result->dhcp_servers as $dhcp_server): ?>
remove [find server="<?php echo $dhcp_server->name ?>"]
<?php foreach ($dhcp_server->hosts as $host): ?>
add address=<?php echo $host->ip_address ?> disabled=no mac-address=<?php echo $host->mac ?> server="<?php echo $host->server ?>" comment="<?php echo text::cs_utf2ascii(text::object_format($host, $host->comment)) ?>"
<?php endforeach ?>
<?php endforeach ?>